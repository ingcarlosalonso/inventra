<?php

namespace App\Services;

use App\Actions\Assistant\ResolveClientMatch;
use App\Actions\Assistant\ResolvePaymentMethodMatch;
use App\Actions\Assistant\ResolvePointOfSaleMatch;
use App\Actions\Assistant\ResolveSaleableItemMatches;
use App\Actions\BuildSaleItemsData;
use App\Enums\DiscountType;
use App\Exceptions\InsufficientStockException;
use App\Models\Client;
use App\Models\Client\Scopes\BySearch as ClientBySearch;
use App\Models\CompositeProduct;
use App\Models\CompositeProduct\Scopes\BySearch as CompositeProductBySearch;
use App\Models\Courier;
use App\Models\Courier\Scopes\BySearch as CourierBySearch;
use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\ByPointOfSaleName as DailyCashByPointOfSaleName;
use App\Models\DailyCash\Scopes\Open;
use App\Models\Order;
use App\Models\Order\Scopes\BySearch as OrderBySearch;
use App\Models\ProductMovement;
use App\Models\ProductMovement\Scopes\BySearch as ProductMovementBySearch;
use App\Models\ProductPresentation;
use App\Models\ProductPresentation\Scopes\BelowMinStock;
use App\Models\ProductPresentation\Scopes\BySearch as ProductPresentationBySearch;
use App\Models\Promotion;
use App\Models\Promotion\Scopes\BySearch as PromotionBySearch;
use App\Models\Quote;
use App\Models\Quote\Scopes\BySearch as QuoteBySearch;
use App\Models\Reception;
use App\Models\Reception\Scopes\BySearch as ReceptionBySearch;
use App\Models\Sale;
use App\Models\Sale\Scopes\ByDateRange as SaleByDateRange;
use App\Models\Sale\Scopes\BySearch as SaleBySearch;
use App\Models\Scopes\Active;
use App\Models\Supplier;
use App\Models\Supplier\Scopes\BySearch as SupplierBySearch;
use App\Models\Tenant;
use App\Models\User;
use App\Models\User\Scopes\BySearch as UserBySearch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Throwable;

class AssistantService
{
    /**
     * Marks the tail of a returned draft/preview message so the *next* request can reliably tell
     * a draft was actually shown to the human — see priorReplyWasADraft() for why this can't just
     * be inferred from what happened earlier in the same model turn.
     */
    private const DRAFT_MARKER = '[DRAFT — nothing has been created yet]';

    private bool $draftShownThisRequest = false;

    public function __construct(
        private readonly ResolveClientMatch $resolveClientMatch,
        private readonly ResolveSaleableItemMatches $resolveSaleableItemMatches,
        private readonly ResolvePaymentMethodMatch $resolvePaymentMethodMatch,
        private readonly ResolvePointOfSaleMatch $resolvePointOfSaleMatch,
        private readonly BuildSaleItemsData $buildSaleItemsData,
        private readonly ProcessSaleService $processSaleService,
        private readonly ProcessOrderService $processOrderService,
        private readonly ProcessQuoteService $processQuoteService,
    ) {}

    private function buildSystemPrompt(): string
    {
        $tenantName = Tenant::current()?->name ?? 'this company';
        $today = now()->toDateString();

        return <<<PROMPT
        You are In-ventra Assistant for **{$tenantName}**.

        TENANT ISOLATION — NON-NEGOTIABLE:
        - You ONLY have access to data belonging to **{$tenantName}**.
        - NEVER discuss, reference, infer, or fabricate data about any other company, tenant, or organisation.
        - If asked about another company or tenant, respond exactly: "I only have access to {$tenantName} data."

        CRITICAL RULES — follow without exception:
        1. ALWAYS call the appropriate tool FIRST before answering ANY question about products, stock, sales, orders, clients, suppliers, cash, quotes, receptions, promotions, movements, or trends.
           For trend/prediction questions: use get_top_selling_products with the relevant date range, then reason about the data.
        2. If a tool returns empty results, report that clearly. NEVER invent or hallucinate data.
        3. Only answer questions related to {$tenantName}'s business data. Politely decline anything outside that scope.

        CREATING SALES, ORDERS OR QUOTES (create_sale, create_order, create_quote):
        - These tools only support plain products (no kits/promotions).
        - If the user only expresses intent ("quiero crear una venta", "necesito un pedido", "I want to create a
          quote") without saying which products/quantities to include, do NOT call the tool yet — ask in plain text
          for the missing details first (client if relevant, products and quantities, discount if any, payment
          method or "on credit"). Only call the tool once you have at least the products and quantities.
        - NEVER call them with confirm=true on the first attempt. Always call with confirm=false first — it resolves
          client/products/payment method and returns a priced draft without creating anything.
        - Show that draft to the user in their own words and wait for an explicit confirmation ("sí", "dale", "confirm").
        - Only then call the same tool again with confirm=true and the exact same parameters.
        - If the tool returns an error (ambiguous or not-found client/product/payment method/point of sale, or a
          permission error), relay it to the user and ask them to clarify — never guess or invent an id.
        - If the user doesn't mention a payment method, leave payments empty — it means the sale/order is fully on
          credit/debt, which is valid.

        Be concise, friendly, and helpful. Format numbers nicely. Respond in the same language as the user.
        Today's date: {$today}
        PROMPT;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, User $user): string
    {
        $this->draftShownThisRequest = false;

        $prismMessages = collect($messages)->map(fn (array $message) => $message['role'] === 'user'
            ? new UserMessage($message['content'])
            : new AssistantMessage($message['content'])
        )->all();

        $systemPrompt = $this->buildSystemPrompt();
        $tools = $this->tools($user, $this->priorReplyWasADraft($messages));

        $models = array_values(array_unique(array_filter([
            config('assistant.model'),
            ...config('assistant.fallback_models', []),
        ])));

        $lastError = null;

        foreach ($models as $model) {
            try {
                $response = Prism::text()
                    ->using(Provider::Groq, $model)
                    ->withSystemPrompt($systemPrompt)
                    ->withMessages($prismMessages)
                    ->withTools($tools)
                    ->withMaxSteps(5)
                    ->generate();

                return $this->draftShownThisRequest
                    ? trim($response->text)."\n\n".self::DRAFT_MARKER
                    : $response->text;
            } catch (Throwable $e) {
                $lastError = $e;
                Log::warning('AI Assistant: Groq model call failed', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::error('AI Assistant: all configured Groq models failed', [
            'models' => $models,
            'error' => $lastError?->getMessage(),
        ]);

        return __('assistant.unavailable');
    }

    /**
     * Whether the human has actually seen a draft: true only when the assistant's most recent
     * reply (i.e. the turn immediately before the message the model is now responding to) carries
     * DRAFT_MARKER, which we append ourselves in chat() — never left to the model to reproduce
     * verbatim, since it typically paraphrases tool results into its own words/language.
     *
     * This is deliberately based on conversation history, not on anything that happened earlier in
     * the *current* model turn: a model can call a tool with confirm=false and then, within the same
     * turn/request (still before the human has seen anything), call it again with confirm=true —
     * which would defeat the whole point of the preview step. Tying "was it shown" to history rather
     * than to same-turn tool calls closes that gap.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function priorReplyWasADraft(array $messages): bool
    {
        // The last element is the user message the model is about to respond to; the one before
        // it, if any, is the assistant's previous reply.
        $priorReply = $messages[count($messages) - 2] ?? null;

        return $priorReply !== null
            && $priorReply['role'] === 'assistant'
            && str_contains($priorReply['content'], self::DRAFT_MARKER);
    }

    /** @return array<int, Tool> */
    private function tools(User $user, bool $draftAlreadyShown = false): array
    {
        return [
            $this->productStockTool(),
            $this->lowStockTool(),
            $this->compositeProductsTool(),
            $this->promotionsTool(),
            $this->salesSummaryTool(),
            $this->recentSalesTool(),
            $this->recentOrdersTool(),
            $this->recentQuotesTool(),
            $this->recentReceptionsTool(),
            $this->dailyCashStatusTool(),
            $this->productMovementsTool(),
            $this->topSellingProductsTool(),
            $this->clientsTool(),
            $this->suppliersTool(),
            $this->usersTool(),
            $this->createSaleTool($user, $draftAlreadyShown),
            $this->createOrderTool($user, $draftAlreadyShown),
            $this->createQuoteTool($user, $draftAlreadyShown),
        ];
    }

    private function productStockTool(): Tool
    {
        return (new Tool)->as('get_product_stock')
            ->for('Get current stock levels for products. Use for questions about stock, inventory, availability, or product prices.')
            ->withStringParameter('search', 'Optional product name or barcode filter (leave empty for all)')
            ->using(function (string $search = '') {
                $query = ProductPresentation::query()
                    ->withScopes(new Active)
                    ->whereHas('product', fn ($q) => $q->withScopes(new Active));

                if ($search !== '') {
                    $query->withScopes(new ProductPresentationBySearch($search));
                }

                $presentations = $query->with(['product.productType', 'presentation.presentationType'])
                    ->limit(50)
                    ->get()
                    ->sortBy(fn ($pp) => $pp->product?->name)
                    ->values();

                if ($presentations->isEmpty()) {
                    return 'No products found.';
                }

                return $presentations->map(fn ($pp) => "- {$pp->product?->name} ({$pp->presentation?->display}) [{$pp->product?->productType?->name}]: stock={$pp->stock}, min={$pp->min_stock}, price={$pp->price}")->join("\n");
            });
    }

    private function lowStockTool(): Tool
    {
        return (new Tool)->as('get_low_stock_products')
            ->for('Get products at or below minimum stock level. Use for low stock alerts or replenishment questions.')
            ->withStringParameter('search', 'Optional product name filter (leave empty for all)')
            ->using(function (string $search = '') {
                $query = ProductPresentation::query()
                    ->withScopes([new Active, new BelowMinStock])
                    ->whereHas('product', fn ($q) => $q->withScopes(new Active));

                if ($search !== '') {
                    $query->withScopes(new ProductPresentationBySearch($search));
                }

                $presentations = $query->with(['product', 'presentation.presentationType'])
                    ->orderByRaw('stock - min_stock ASC')
                    ->limit(50)
                    ->get();

                if ($presentations->isEmpty()) {
                    return 'No products below minimum stock. All good!';
                }

                return "Low stock products ({$presentations->count()}):\n"
                    .$presentations->map(fn ($pp) => "- {$pp->product?->name} ({$pp->presentation?->display}): stock={$pp->stock} (min={$pp->min_stock})")->join("\n");
            });
    }

    private function compositeProductsTool(): Tool
    {
        return (new Tool)->as('get_composite_products')
            ->for('Get composite products (kits/bundles) and their components. Use for any question about kits or bundles.')
            ->withStringParameter('search', 'Optional name filter (leave empty for all)')
            ->using(function (string $search = '') {
                $query = CompositeProduct::query()->withScopes(new Active);

                if ($search !== '') {
                    $query->withScopes(new CompositeProductBySearch($search));
                }

                $composites = $query->with('items.product')->orderBy('name')->limit(30)->get();

                if ($composites->isEmpty()) {
                    return 'No composite products configured.';
                }

                return "Composite products ({$composites->count()}):\n"
                    .$composites->map(function ($cp) {
                        $components = $cp->items->map(fn ($item) => "{$item->quantity}x {$item->product?->name}")->join(', ');

                        return "- {$cp->name}".($cp->code ? " ({$cp->code})" : '').": {$components}";
                    })->join("\n");
            });
    }

    private function promotionsTool(): Tool
    {
        return (new Tool)->as('get_promotions')
            ->for('Get active promotions and their included products. Use for questions about promotions, offers, or deals.')
            ->withStringParameter('search', 'Optional promotion name filter (leave empty for all)')
            ->using(function (string $search = '') {
                $query = Promotion::query()->withScopes(new Active);

                if ($search !== '') {
                    $query->withScopes(new PromotionBySearch($search));
                }

                $promotions = $query->with('items.product')->orderBy('name')->limit(30)->get();

                if ($promotions->isEmpty()) {
                    return 'No active promotions found.';
                }

                return "Active promotions ({$promotions->count()}):\n"
                    .$promotions->map(function ($promo) {
                        $items = $promo->items->map(fn ($item) => "{$item->quantity}x {$item->product?->name}")->join(', ');

                        return "- {$promo->name}".($promo->sale_price ? " (price={$promo->sale_price})" : '').": {$items}";
                    })->join("\n");
            });
    }

    private function salesSummaryTool(): Tool
    {
        return (new Tool)->as('get_sales_summary')
            ->for('Get sales statistics (count, revenue, average) for a date range. Use for revenue questions or totals.')
            ->withStringParameter('from', 'Start date YYYY-MM-DD (defaults to today)')
            ->withStringParameter('to', 'End date YYYY-MM-DD (defaults to today)')
            ->using(function (string $from = '', string $to = '') {
                $fromDate = Carbon::parse($from ?: now()->toDateString());
                $toDate = Carbon::parse($to ?: now()->toDateString());

                $sales = Sale::query()->withScopes(new SaleByDateRange($fromDate, $toDate))->get();

                if ($sales->isEmpty()) {
                    return "No sales found from {$fromDate->toDateString()} to {$toDate->toDateString()}.";
                }

                $total = $sales->sum('total');
                $avg = $sales->avg('total');

                return "Sales {$fromDate->toDateString()} → {$toDate->toDateString()}:\n"
                    ."- Count: {$sales->count()}\n"
                    .'- Revenue: '.number_format((float) $total, 2)."\n"
                    .'- Average ticket: '.number_format((float) $avg, 2);
            });
    }

    private function recentSalesTool(): Tool
    {
        return (new Tool)->as('get_recent_sales')
            ->for('Get recent individual sales with client, total and state. Use for listing sales or searching a specific sale.')
            ->withStringParameter('search', 'Optional client name or sale notes filter')
            ->withStringParameter('limit', 'How many to return (default 10)')
            ->using(function (string $search = '', string $limit = '10') {
                $query = Sale::query()->with(['client', 'saleState'])->latest();

                if ($search !== '') {
                    $query->withScopes(new SaleBySearch($search));
                }

                $sales = $query->limit((int) min((int) $limit, 50))->get();

                if ($sales->isEmpty()) {
                    return 'No sales found.';
                }

                return $sales->map(fn ($s) => "- #{$s->id} | {$s->client?->name} | {$s->saleState?->name} | total={$s->total} | {$s->created_at->format('d/m/Y H:i')}")->join("\n");
            });
    }

    private function recentOrdersTool(): Tool
    {
        return (new Tool)->as('get_recent_orders')
            ->for('Get recent delivery orders with client, state and courier. Use for order or delivery questions.')
            ->withStringParameter('search', 'Optional client name filter')
            ->withStringParameter('state', 'Optional order state name filter')
            ->withStringParameter('limit', 'How many to return (default 10)')
            ->using(function (string $search = '', string $state = '', string $limit = '10') {
                $query = Order::query()->with(['client', 'orderState', 'courier'])->latest();

                if ($search !== '') {
                    $query->withScopes(new OrderBySearch($search));
                }

                $orders = $query->limit((int) min((int) $limit, 50))->get();

                if ($orders->isEmpty()) {
                    return 'No orders found.';
                }

                return $orders->map(function ($o) {
                    $courier = $o->courier ? " | courier={$o->courier->name}" : '';
                    $scheduled = $o->scheduled_at ? " | scheduled={$o->scheduled_at}" : '';

                    return "- #{$o->id} | {$o->client?->name} | {$o->orderState?->name}{$courier}{$scheduled} | {$o->created_at->format('d/m/Y')}";
                })->join("\n");
            });
    }

    private function recentQuotesTool(): Tool
    {
        return (new Tool)->as('get_recent_quotes')
            ->for('Get recent quotes/budgets with client and total. Use for quote or budget questions.')
            ->withStringParameter('search', 'Optional client name filter')
            ->withStringParameter('limit', 'How many to return (default 10)')
            ->using(function (string $search = '', string $limit = '10') {
                $query = Quote::query()->with('client')->latest();

                if ($search !== '') {
                    $query->withScopes(new QuoteBySearch($search));
                }

                $quotes = $query->limit((int) min((int) $limit, 50))->get();

                if ($quotes->isEmpty()) {
                    return 'No quotes found.';
                }

                return $quotes->map(fn ($q) => "- #{$q->id} | {$q->client?->name} | total={$q->total} | expires={$q->expires_at} | {$q->created_at->format('d/m/Y')}")->join("\n");
            });
    }

    private function recentReceptionsTool(): Tool
    {
        return (new Tool)->as('get_recent_receptions')
            ->for('Get recent merchandise receptions from suppliers. Use for purchase or reception questions.')
            ->withStringParameter('search', 'Optional supplier name filter')
            ->withStringParameter('limit', 'How many to return (default 10)')
            ->using(function (string $search = '', string $limit = '10') {
                $query = Reception::query()->with(['supplier', 'items.productPresentation.product'])->latest();

                if ($search !== '') {
                    $query->withScopes(new ReceptionBySearch($search));
                }

                $receptions = $query->limit((int) min((int) $limit, 50))->get();

                if ($receptions->isEmpty()) {
                    return 'No receptions found.';
                }

                return $receptions->map(function ($r) {
                    $items = $r->items->map(fn ($i) => "{$i->quantity}x {$i->productPresentation?->product?->name}")->join(', ');

                    return "- #{$r->id} | {$r->supplier?->name} | total={$r->total} | {$r->received_at->format('d/m/Y')} | [{$items}]";
                })->join("\n");
            });
    }

    private function dailyCashStatusTool(): Tool
    {
        return (new Tool)->as('get_daily_cash_status')
            ->for('Get open daily cash registers with balance and movements. Use for cash, register or balance questions.')
            ->withStringParameter('point_of_sale', 'Optional point of sale name filter (leave empty for all)')
            ->using(function (string $point_of_sale = '') {
                $query = DailyCash::query()
                    ->withScopes(new Open)
                    ->with(['pointOfSale', 'cashMovements.cashMovementType']);

                if ($point_of_sale !== '') {
                    $query->withScopes(new DailyCashByPointOfSaleName($point_of_sale));
                }

                $cashes = $query->get();

                if ($cashes->isEmpty()) {
                    return 'No open daily cash registers at the moment.';
                }

                return $cashes->map(function ($c) {
                    $income = $c->cashMovements->where('cashMovementType.is_income', true)->sum('amount');
                    $expenses = $c->cashMovements->where('cashMovementType.is_income', false)->sum('amount');

                    return "- {$c->pointOfSale?->name}: opening={$c->opening_balance}, income={$income}, expenses={$expenses}, opened={$c->opened_at}";
                })->join("\n");
            });
    }

    private function productMovementsTool(): Tool
    {
        return (new Tool)->as('get_product_movements')
            ->for('Get recent stock movements (entries, adjustments, losses) for products.')
            ->withStringParameter('search', 'Optional product name filter')
            ->withStringParameter('limit', 'How many to return (default 15)')
            ->using(function (string $search = '', string $limit = '15') {
                $query = ProductMovement::query()
                    ->with(['product', 'productMovementType', 'user'])
                    ->latest();

                if ($search !== '') {
                    $query->withScopes(new ProductMovementBySearch($search));
                }

                $movements = $query->limit((int) min((int) $limit, 50))->get();

                if ($movements->isEmpty()) {
                    return 'No product movements found.';
                }

                return $movements->map(fn ($m) => "- {$m->created_at->format('d/m/Y')} | {$m->product?->name} | {$m->productMovementType?->name} | qty={$m->quantity} | {$m->user?->name}")->join("\n");
            });
    }

    private function topSellingProductsTool(): Tool
    {
        return (new Tool)->as('get_top_selling_products')
            ->for('Get best selling products by quantity and revenue for a date range. Use this for sales trends, predictions, or ranking questions.')
            ->withStringParameter('from', 'Start date YYYY-MM-DD (defaults to this month start)')
            ->withStringParameter('to', 'End date YYYY-MM-DD (defaults to today)')
            ->withStringParameter('limit', 'How many products to return (default 10)')
            ->using(function (string $from = '', string $to = '', string $limit = '10') {
                $fromDate = Carbon::parse($from ?: now()->startOfMonth()->toDateString());
                $toDate = Carbon::parse($to ?: now()->toDateString());

                $products = Sale::query()
                    ->withScopes(new SaleByDateRange($fromDate, $toDate))
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('product_presentations', 'sale_items.product_presentation_id', '=', 'product_presentations.id')
                    ->join('products', 'product_presentations.product_id', '=', 'products.id')
                    ->whereNull('products.deleted_at')
                    ->selectRaw('products.id, products.name, SUM(sale_items.quantity) as total_qty, SUM(sale_items.total) as total_revenue')
                    ->groupBy('products.id', 'products.name')
                    ->orderByDesc('total_revenue')
                    ->limit((int) min((int) $limit, 50))
                    ->get();

                if ($products->isEmpty()) {
                    return "No sales found between {$fromDate->toDateString()} and {$toDate->toDateString()}.";
                }

                return "Top selling products ({$fromDate->toDateString()} → {$toDate->toDateString()}):\n"
                    .$products->map(fn ($p) => "- {$p->name}: qty=".number_format((float) $p->total_qty, 2).', revenue='.number_format((float) $p->total_revenue, 2))->join("\n");
            });
    }

    private function clientsTool(): Tool
    {
        return (new Tool)->as('get_clients')
            ->for('Search or list active clients. Use for customer questions.')
            ->withStringParameter('search', 'Optional client name, email or phone filter')
            ->using(function (string $search = '') {
                $query = Client::query()->withScopes(new Active);

                if ($search !== '') {
                    $query->withScopes(new ClientBySearch($search));
                }

                $clients = $query->orderBy('last_name')->orderBy('first_name')->limit(20)->get();

                if ($clients->isEmpty()) {
                    return 'No clients found.';
                }

                return $clients->map(fn ($c) => "- {$c->name}".($c->phone ? " | {$c->phone}" : '').($c->email ? " | {$c->email}" : ''))->join("\n");
            });
    }

    private function suppliersTool(): Tool
    {
        return (new Tool)->as('get_suppliers')
            ->for('Search or list active suppliers/providers. Use for supplier questions.')
            ->withStringParameter('search', 'Optional supplier name filter')
            ->using(function (string $search = '') {
                $query = Supplier::query()->withScopes(new Active);

                if ($search !== '') {
                    $query->withScopes(new SupplierBySearch($search));
                }

                $suppliers = $query->orderBy('name')->limit(20)->get();

                if ($suppliers->isEmpty()) {
                    return 'No suppliers found.';
                }

                return $suppliers->map(fn ($s) => "- {$s->name}".($s->contact_name ? " ({$s->contact_name})" : '').($s->phone ? " | {$s->phone}" : ''))->join("\n");
            });
    }

    private function usersTool(): Tool
    {
        return (new Tool)->as('get_users')
            ->for('Search or list system users. Use for questions about who uses the system or user roles.')
            ->withStringParameter('search', 'Optional user name or email filter')
            ->using(function (string $search = '') {
                $query = User::query();

                if ($search !== '') {
                    $query->withScopes(new UserBySearch($search));
                }

                $users = $query->with('roles')->orderBy('name')->limit(20)->get();

                if ($users->isEmpty()) {
                    return 'No users found.';
                }

                return $users->map(fn ($u) => "- {$u->name}".($u->roles->isNotEmpty() ? ' | roles: '.$u->roles->pluck('name')->join(', ') : ''))->join("\n");
            });
    }

    // ─── Mutating tools (create sale / order / quote) ────────────────────────
    //
    // All three share the same safety protocol: they resolve every free-text
    // reference (client, product, payment method, point of sale) server-side —
    // the model never supplies an id — and never write to the database unless
    // called with confirm=true, which only happens after the model has shown a
    // draft to the user and the user explicitly agreed to it.

    private function itemSchema(): ObjectSchema
    {
        return new ObjectSchema(
            'item',
            'A product line, exactly as described by the user',
            [
                new StringSchema('search', 'Product name or barcode as mentioned by the user'),
                new NumberSchema('quantity', 'Quantity requested'),
            ],
            ['search', 'quantity'],
        );
    }

    private function paymentSchema(): ObjectSchema
    {
        return new ObjectSchema(
            'payment',
            'A payment applied at creation time',
            [
                new StringSchema('method_search', 'Payment method name as mentioned by the user (e.g. "efectivo", "tarjeta")'),
                new NumberSchema('amount', 'Amount paid with this method'),
            ],
            ['method_search', 'amount'],
        );
    }

    /**
     * A plain nullable string, not an enum: providers validate tool-call arguments against the JSON
     * schema before our closure ever runs, and Prism's EnumSchema doesn't add "null" to its "enum"
     * list even when marked nullable — so a model sending null (or "") for "no discount" gets the
     * whole call rejected. We validate the two allowed values ourselves in normalizeDiscountType().
     */
    private function discountTypeSchema(): StringSchema
    {
        return new StringSchema(
            'discount_type',
            'Overall discount type, if the user asked for one: "percentage" or "fixed". Omit for no discount.',
            nullable: true,
        );
    }

    /** Nullable for the same reason as discountTypeSchema() — "no discount" has to be a legal value. */
    private function discountValueSchema(): NumberSchema
    {
        return new NumberSchema(
            'discount_value',
            'Overall discount value (percentage or fixed amount). Omit for no discount.',
            nullable: true,
        );
    }

    /**
     * Every optional string/boolean parameter across the mutating tools uses this instead of
     * withStringParameter()/withBooleanParameter() (neither exposes a nullable option): a model
     * asked for an "optional" field very reasonably sends an explicit null when it doesn't apply,
     * and a non-nullable schema gets the whole tool call rejected by the provider before our code
     * ever runs — the exact same class of bug as discount_type/discount_value.
     */
    private function nullableStringSchema(string $name, string $description): StringSchema
    {
        return new StringSchema($name, $description, nullable: true);
    }

    private function nullableBooleanSchema(string $name, string $description): BooleanSchema
    {
        return new BooleanSchema($name, $description, nullable: true);
    }

    private function normalizeDiscountType(?string $discountType): ?string
    {
        return in_array($discountType, [DiscountType::Percentage->value, DiscountType::Fixed->value], true)
            ? $discountType
            : null;
    }

    /** @return array{uuid: string|null, label: string, error: string|null} */
    private function resolveClientForTool(string $search): array
    {
        if ($search === '') {
            return ['uuid' => null, 'label' => 'walk-in customer (none)', 'error' => null];
        }

        $matches = $this->resolveClientMatch->execute($search);

        if ($matches->isEmpty()) {
            return ['uuid' => null, 'label' => '', 'error' => "No client found matching \"{$search}\". Ask the user to clarify the name."];
        }

        if ($matches->count() > 1) {
            return ['uuid' => null, 'label' => '', 'error' => 'Multiple clients match "'.$search.'": '.$matches->pluck('name')->join(', ').'. Ask the user which one they mean.'];
        }

        $client = $matches->first();

        return ['uuid' => $client->uuid, 'label' => $client->name, 'error' => null];
    }

    /** @return array{uuid: string|null, label: string, error: string|null} */
    private function resolvePointOfSaleForTool(string $search, bool $required): array
    {
        $matches = $this->resolvePointOfSaleMatch->execute($search);

        if ($search === '') {
            if ($matches->count() === 1) {
                $pos = $matches->first();

                return ['uuid' => $pos->uuid, 'label' => $pos->name, 'error' => null];
            }

            if (! $required) {
                return ['uuid' => null, 'label' => 'none', 'error' => null];
            }

            $error = $matches->isEmpty()
                ? 'No active points of sale are configured. Tell the user one needs to be set up before creating sales.'
                : 'Multiple points of sale exist ('.$matches->pluck('name')->join(', ').'). Ask the user which one to use.';

            return ['uuid' => null, 'label' => 'none', 'error' => $error];
        }

        if ($matches->isEmpty()) {
            return ['uuid' => null, 'label' => 'none', 'error' => "No point of sale found matching \"{$search}\"."];
        }

        if ($matches->count() > 1) {
            return ['uuid' => null, 'label' => 'none', 'error' => 'Multiple points of sale match "'.$search.'": '.$matches->pluck('name')->join(', ').'. Ask the user to clarify.'];
        }

        $pos = $matches->first();

        return ['uuid' => $pos->uuid, 'label' => $pos->name, 'error' => null];
    }

    /**
     * @param  array<int, array{search: string, quantity: float|int|string}>  $items
     * @return array{items: array<int, array<string, mixed>>, labels: array<int, string>, error: string|null}
     */
    private function resolveItemsForTool(array $items): array
    {
        if ($items === []) {
            return ['items' => [], 'labels' => [], 'error' => 'No items specified. Ask the user which products and quantities to include.'];
        }

        $problems = [];
        $built = [];
        $labels = [];

        foreach ($this->resolveSaleableItemMatches->execute($items) as $resolution) {
            if ($resolution['status'] === 'not_found') {
                $problems[] = "No product found matching \"{$resolution['query']}\".";

                continue;
            }

            if ($resolution['status'] === 'ambiguous') {
                $problems[] = "Multiple products match \"{$resolution['query']}\": ".implode(', ', $resolution['candidates']).'. Ask the user to clarify.';

                continue;
            }

            if ($resolution['quantity'] <= 0) {
                $problems[] = "Invalid quantity for \"{$resolution['description']}\" — it must be greater than zero.";

                continue;
            }

            $built[] = [
                'item_type' => $resolution['item_type'],
                'saleable_id' => $resolution['saleable_id'],
                'description' => $resolution['description'],
                'quantity' => $resolution['quantity'],
                'unit_price' => $resolution['unit_price'],
                'discount_type' => null,
                'discount_value' => null,
            ];
            $labels[] = "{$resolution['quantity']}x {$resolution['description']} @ {$resolution['unit_price']}";
        }

        if ($problems !== []) {
            return ['items' => [], 'labels' => [], 'error' => implode(' ', $problems)];
        }

        return ['items' => $built, 'labels' => $labels, 'error' => null];
    }

    /**
     * @param  array<int, array{method_search: string, amount: float|int|string}>  $payments
     * @return array{payments: array<int, array{payment_method_id: string, amount: float}>, labels: array<int, string>, error: string|null}
     */
    private function resolvePaymentsForTool(array $payments): array
    {
        $resolved = [];
        $labels = [];

        foreach ($payments as $payment) {
            $search = trim((string) ($payment['method_search'] ?? ''));
            $amount = (float) ($payment['amount'] ?? 0);

            if ($search === '' || $amount <= 0) {
                return ['payments' => [], 'labels' => [], 'error' => 'Each payment needs a method_search and a positive amount.'];
            }

            $matches = $this->resolvePaymentMethodMatch->execute($search);

            if ($matches->isEmpty()) {
                return ['payments' => [], 'labels' => [], 'error' => "No payment method found matching \"{$search}\"."];
            }

            if ($matches->count() > 1) {
                return ['payments' => [], 'labels' => [], 'error' => 'Multiple payment methods match "'.$search.'": '.$matches->pluck('name')->join(', ').'. Ask the user to clarify.'];
            }

            $method = $matches->first();
            $resolved[] = ['payment_method_id' => $method->uuid, 'amount' => $amount];
            $labels[] = "{$method->name} {$amount}";
        }

        return ['payments' => $resolved, 'labels' => $labels, 'error' => null];
    }

    /**
     * @param  array<int, string>  $itemLabels
     * @param  array<int, string>|null  $paymentLabels
     * @param  array{subtotal: float, discount_amount: float, total: float}  $totals
     * @param  array<int, string>  $extraLines
     */
    private function draftSummary(
        string $clientLabel,
        ?string $pointOfSaleLabel,
        array $itemLabels,
        ?array $paymentLabels,
        array $totals,
        string $toolName,
        array $extraLines = [],
    ): string {
        $this->draftShownThisRequest = true;

        $lines = ['PREVIEW ONLY — nothing has been created yet.', "Client: {$clientLabel}"];

        if ($pointOfSaleLabel !== null) {
            $lines[] = "Point of sale: {$pointOfSaleLabel}";
        }

        array_push($lines, ...$extraLines);

        $lines[] = "Items:\n- ".implode("\n- ", $itemLabels);
        $lines[] = 'Subtotal: '.number_format($totals['subtotal'], 2);

        if ($totals['discount_amount'] > 0) {
            $lines[] = 'Discount: '.number_format($totals['discount_amount'], 2);
        }

        $lines[] = 'Total: '.number_format($totals['total'], 2);

        if ($paymentLabels !== null) {
            $lines[] = $paymentLabels !== []
                ? 'Payments: '.implode(', ', $paymentLabels)
                : 'Payments: none registered — the full amount remains as debt/credit.';
        }

        $lines[] = "Show this draft to the user in their own language and ask them to explicitly confirm. Only if they confirm, call {$toolName} again with the exact same parameters plus confirm=true.";

        return implode("\n", $lines);
    }

    /** @param  array<int, array{payment_method_id: string, amount: float}>|null  $payments */
    private function creationSuccess(string $label, int $id, float $total, ?array $payments = null): string
    {
        if ($payments === null) {
            return "{$label} #{$id} created successfully. Total: ".number_format($total, 2).'.';
        }

        $paid = array_sum(array_column($payments, 'amount'));
        $balance = round($total - $paid, 2);

        $status = match (true) {
            $balance <= 0 => 'fully paid',
            $paid <= 0 => 'fully on credit/debt',
            default => 'partially paid, remaining balance '.number_format($balance, 2),
        };

        return "{$label} #{$id} created successfully. Total: ".number_format($total, 2).". Payment status: {$status}.";
    }

    private function createSaleTool(User $user, bool $draftAlreadyShown): Tool
    {
        return (new Tool)->as('create_sale')
            ->for(
                'Create a new sale (venta) for the current tenant. ALWAYS call this first with confirm=false to get '
                .'a priced preview, show that preview to the user, and only call it again with confirm=true after '
                .'the user explicitly confirms. Only handles plain products (no kits/promotions).'
            )
            ->withParameter($this->nullableBooleanSchema('confirm', 'false to preview without creating anything, true to actually create the sale after the user confirmed'), required: false)
            ->withParameter($this->nullableStringSchema('client_search', 'Client name, phone or email as mentioned by the user. Leave empty for a walk-in sale with no client.'), required: false)
            ->withParameter($this->nullableStringSchema('point_of_sale_search', 'Point of sale name as mentioned by the user. Leave empty to auto-select it if the tenant only has one.'), required: false)
            ->withArrayParameter('items', 'Products and quantities to include', $this->itemSchema())
            ->withParameter($this->discountTypeSchema(), required: false)
            ->withParameter($this->discountValueSchema(), required: false)
            ->withArrayParameter('payments', 'Payments received now. Leave empty if the sale is fully on credit/debt.', $this->paymentSchema(), required: false)
            ->withParameter($this->nullableStringSchema('notes', 'Optional free-text notes for the sale'), required: false)
            ->using(function (
                ?bool $confirm = null,
                ?string $client_search = null,
                ?string $point_of_sale_search = null,
                array $items = [],
                ?string $discount_type = null,
                ?float $discount_value = null,
                array $payments = [],
                ?string $notes = null,
            ) use ($user, $draftAlreadyShown) {
                if ($user->cannot('create_edit_delete_sales')) {
                    return "PERMISSION_DENIED: the current user isn't allowed to create sales. Tell them and don't retry.";
                }

                $confirm ??= false;

                if ($confirm && ! $draftAlreadyShown) {
                    return 'You must call this tool with confirm=false first, show the resulting draft to the '
                        .'user, and wait for their next message to explicitly confirm it before calling confirm=true.';
                }

                $client_search ??= '';
                $point_of_sale_search ??= '';
                $notes ??= '';
                $discount_type = $this->normalizeDiscountType($discount_type);
                $discount_value ??= 0.0;

                $client = $this->resolveClientForTool($client_search);
                if ($client['error']) {
                    return $client['error'];
                }

                $pointOfSale = $this->resolvePointOfSaleForTool($point_of_sale_search, required: true);
                if ($pointOfSale['error']) {
                    return $pointOfSale['error'];
                }

                $resolvedItems = $this->resolveItemsForTool($items);
                if ($resolvedItems['error']) {
                    return $resolvedItems['error'];
                }

                $resolvedPayments = $this->resolvePaymentsForTool($payments);
                if ($resolvedPayments['error']) {
                    return $resolvedPayments['error'];
                }

                $data = [
                    'client_id' => $client['uuid'],
                    'point_of_sale_id' => $pointOfSale['uuid'],
                    'sale_state_id' => null,
                    'currency_id' => null,
                    'notes' => $notes !== '' ? $notes : null,
                    'discount_type' => $discount_type,
                    'discount_value' => $discount_value,
                    'items' => $resolvedItems['items'],
                    'payments' => $resolvedPayments['payments'],
                ];

                $totals = $this->buildSaleItemsData->execute($data['items'], $discount_type, $discount_value);

                if (! $confirm) {
                    return $this->draftSummary($client['label'], $pointOfSale['label'], $resolvedItems['labels'], $resolvedPayments['labels'], $totals, 'create_sale');
                }

                try {
                    $sale = $this->processSaleService->execute($data, $user->id);
                } catch (InsufficientStockException $e) {
                    return $e->getMessage();
                }

                return $this->creationSuccess('Sale', $sale->id, $totals['total'], $resolvedPayments['payments']);
            });
    }

    private function createOrderTool(User $user, bool $draftAlreadyShown): Tool
    {
        return (new Tool)->as('create_order')
            ->for(
                'Create a new delivery order (pedido) for the current tenant. ALWAYS call this first with '
                .'confirm=false to get a priced preview, show it to the user, and only call it again with '
                .'confirm=true after the user explicitly confirms. Only handles plain products (no kits/promotions).'
            )
            ->withParameter($this->nullableBooleanSchema('confirm', 'false to preview without creating anything, true to actually create the order after the user confirmed'), required: false)
            ->withParameter($this->nullableStringSchema('client_search', 'Client name, phone or email as mentioned by the user. Leave empty for no client.'), required: false)
            ->withParameter($this->nullableStringSchema('point_of_sale_search', 'Point of sale name as mentioned by the user, if any'), required: false)
            ->withParameter($this->nullableStringSchema('courier_search', 'Delivery courier name as mentioned by the user, if any'), required: false)
            ->withParameter($this->nullableStringSchema('address', 'Delivery address, if mentioned'), required: false)
            ->withParameter($this->nullableBooleanSchema('requires_delivery', 'Whether the order requires delivery'), required: false)
            ->withParameter($this->nullableStringSchema('scheduled_at', 'Scheduled delivery date/time (YYYY-MM-DD or YYYY-MM-DD HH:MM), if mentioned'), required: false)
            ->withArrayParameter('items', 'Products and quantities to include', $this->itemSchema())
            ->withParameter($this->discountTypeSchema(), required: false)
            ->withParameter($this->discountValueSchema(), required: false)
            ->withArrayParameter('payments', 'Payments received now. Leave empty if the order is fully on credit/debt.', $this->paymentSchema(), required: false)
            ->withParameter($this->nullableStringSchema('notes', 'Optional free-text notes for the order'), required: false)
            ->using(function (
                ?bool $confirm = null,
                ?string $client_search = null,
                ?string $point_of_sale_search = null,
                ?string $courier_search = null,
                ?string $address = null,
                ?bool $requires_delivery = null,
                ?string $scheduled_at = null,
                array $items = [],
                ?string $discount_type = null,
                ?float $discount_value = null,
                array $payments = [],
                ?string $notes = null,
            ) use ($user, $draftAlreadyShown) {
                if ($user->cannot('create_edit_delete_orders')) {
                    return "PERMISSION_DENIED: the current user isn't allowed to create orders. Tell them and don't retry.";
                }

                $confirm ??= false;

                if ($confirm && ! $draftAlreadyShown) {
                    return 'You must call this tool with confirm=false first, show the resulting draft to the '
                        .'user, and wait for their next message to explicitly confirm it before calling confirm=true.';
                }

                $client_search ??= '';
                $point_of_sale_search ??= '';
                $courier_search ??= '';
                $address ??= '';
                $requires_delivery ??= false;
                $scheduled_at ??= '';
                $notes ??= '';
                $discount_type = $this->normalizeDiscountType($discount_type);
                $discount_value ??= 0.0;

                $client = $this->resolveClientForTool($client_search);
                if ($client['error']) {
                    return $client['error'];
                }

                $pointOfSale = $this->resolvePointOfSaleForTool($point_of_sale_search, required: false);
                if ($pointOfSale['error']) {
                    return $pointOfSale['error'];
                }

                $courierUuid = null;
                $courierLabel = null;

                if ($courier_search !== '') {
                    $couriers = Courier::query()->withScopes([new Active, new CourierBySearch($courier_search)])->limit(5)->get();

                    if ($couriers->isEmpty()) {
                        return "No courier found matching \"{$courier_search}\".";
                    }

                    if ($couriers->count() > 1) {
                        return 'Multiple couriers match "'.$courier_search.'": '.$couriers->pluck('name')->join(', ').'. Ask the user to clarify.';
                    }

                    $courierUuid = $couriers->first()->uuid;
                    $courierLabel = $couriers->first()->name;
                }

                $resolvedItems = $this->resolveItemsForTool($items);
                if ($resolvedItems['error']) {
                    return $resolvedItems['error'];
                }

                $resolvedPayments = $this->resolvePaymentsForTool($payments);
                if ($resolvedPayments['error']) {
                    return $resolvedPayments['error'];
                }

                $data = [
                    'client_id' => $client['uuid'],
                    'courier_id' => $courierUuid,
                    'order_state_id' => null,
                    'point_of_sale_id' => $pointOfSale['uuid'],
                    'sale_id' => null,
                    'currency_id' => null,
                    'address' => $address !== '' ? $address : null,
                    'notes' => $notes !== '' ? $notes : null,
                    'requires_delivery' => $requires_delivery,
                    'delivery_date' => null,
                    'scheduled_at' => $scheduled_at !== '' ? $scheduled_at : null,
                    'discount_type' => $discount_type,
                    'discount_value' => $discount_value,
                    'items' => $resolvedItems['items'],
                    'payments' => $resolvedPayments['payments'],
                ];

                $totals = $this->buildSaleItemsData->execute($data['items'], $discount_type, $discount_value);

                if (! $confirm) {
                    $extraLines = array_filter([
                        $courierLabel !== null ? "Courier: {$courierLabel}" : null,
                        $address !== '' ? "Address: {$address}" : null,
                        $scheduled_at !== '' ? "Scheduled at: {$scheduled_at}" : null,
                    ]);

                    return $this->draftSummary($client['label'], $pointOfSale['label'], $resolvedItems['labels'], $resolvedPayments['labels'], $totals, 'create_order', array_values($extraLines));
                }

                try {
                    $order = $this->processOrderService->execute($data, $user->id);
                } catch (InsufficientStockException $e) {
                    return $e->getMessage();
                }

                return $this->creationSuccess('Order', $order->id, $totals['total'], $resolvedPayments['payments']);
            });
    }

    private function createQuoteTool(User $user, bool $draftAlreadyShown): Tool
    {
        return (new Tool)->as('create_quote')
            ->for(
                'Create a new quote/budget (presupuesto) for the current tenant. ALWAYS call this first with '
                .'confirm=false to get a priced preview, show it to the user, and only call it again with '
                .'confirm=true after the user explicitly confirms. Only handles plain products (no kits/promotions). '
                .'Quotes have no payments.'
            )
            ->withParameter($this->nullableBooleanSchema('confirm', 'false to preview without creating anything, true to actually create the quote after the user confirmed'), required: false)
            ->withParameter($this->nullableStringSchema('client_search', 'Client name, phone or email as mentioned by the user. Leave empty for no client.'), required: false)
            ->withArrayParameter('items', 'Products and quantities to include', $this->itemSchema())
            ->withParameter($this->discountTypeSchema(), required: false)
            ->withParameter($this->discountValueSchema(), required: false)
            ->withParameter($this->nullableStringSchema('expires_at', 'Expiration date (YYYY-MM-DD), if mentioned'), required: false)
            ->withParameter($this->nullableStringSchema('notes', 'Optional free-text notes for the quote'), required: false)
            ->using(function (
                bool $confirm = false,
                string $client_search = '',
                array $items = [],
                ?string $discount_type = null,
                ?float $discount_value = null,
                string $expires_at = '',
                string $notes = '',
            ) use ($user, $draftAlreadyShown) {
                if ($user->cannot('create_edit_delete_quotes')) {
                    return "PERMISSION_DENIED: the current user isn't allowed to create quotes. Tell them and don't retry.";
                }

                if ($confirm && ! $draftAlreadyShown) {
                    return 'You must call this tool with confirm=false first, show the resulting draft to the '
                        .'user, and wait for their next message to explicitly confirm it before calling confirm=true.';
                }

                $discount_type = $this->normalizeDiscountType($discount_type);
                $discount_value ??= 0.0;

                $client = $this->resolveClientForTool($client_search);
                if ($client['error']) {
                    return $client['error'];
                }

                $resolvedItems = $this->resolveItemsForTool($items);
                if ($resolvedItems['error']) {
                    return $resolvedItems['error'];
                }

                $data = [
                    'client_id' => $client['uuid'],
                    'currency_id' => null,
                    'notes' => $notes !== '' ? $notes : null,
                    'discount_type' => $discount_type,
                    'discount_value' => $discount_value,
                    'starts_at' => null,
                    'expires_at' => $expires_at !== '' ? $expires_at : null,
                    'items' => $resolvedItems['items'],
                ];

                $totals = $this->buildSaleItemsData->execute($data['items'], $discount_type, $discount_value);

                if (! $confirm) {
                    $extraLines = $expires_at !== '' ? ["Expires at: {$expires_at}"] : [];

                    return $this->draftSummary($client['label'], null, $resolvedItems['labels'], null, $totals, 'create_quote', $extraLines);
                }

                $quote = $this->processQuoteService->execute($data, $user->id);

                return $this->creationSuccess('Quote', $quote->id, $totals['total']);
            });
    }
}
