<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Client\Scopes\BySearch as ClientBySearch;
use App\Models\CompositeProduct;
use App\Models\CompositeProduct\Scopes\BySearch as CompositeProductBySearch;
use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\Open;
use App\Models\Order;
use App\Models\Order\Scopes\BySearch as OrderBySearch;
use App\Models\Product;
use App\Models\Product\Scopes\BelowMinStock;
use App\Models\Product\Scopes\BySearch as ProductBySearch;
use App\Models\ProductMovement;
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
use App\Models\User;
use App\Models\User\Scopes\BySearch as UserBySearch;
use Illuminate\Support\Carbon;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Tool;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class AssistantService
{
    private const MODEL = 'meta-llama/llama-4-scout-17b-16e-instruct';

    private const SYSTEM_PROMPT = <<<'PROMPT'
        You are In-ventra Assistant, an AI helper embedded in the In-ventra inventory and sales management system.

        CRITICAL RULES — follow without exception:
        1. NEVER say you don't have access to data or that information is unavailable. You ALWAYS have access via tools.
        2. ALWAYS call the appropriate tool FIRST before answering ANY question about products, stock, sales, orders, clients, suppliers, cash, quotes, receptions, promotions, movements, or trends.
           For trend/prediction questions: use get_top_selling_products with the relevant date range, then reason about the data.
        3. If a tool returns empty results, report that clearly. Never skip the tool call.

        Be concise, friendly, and helpful. Format numbers nicely. Respond in the same language as the user.
        Today's date: %s
        PROMPT;

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages): string
    {
        $prismMessages = collect($messages)->map(fn (array $message) => $message['role'] === 'user'
            ? new UserMessage($message['content'])
            : new AssistantMessage($message['content'])
        )->all();

        $response = Prism::text()
            ->using(Provider::Groq, self::MODEL)
            ->withSystemPrompt(sprintf(self::SYSTEM_PROMPT, now()->toDateString()))
            ->withMessages($prismMessages)
            ->withTools($this->tools())
            ->withMaxSteps(5)
            ->generate();

        return $response->text;
    }

    /** @return array<int, Tool> */
    private function tools(): array
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
        ];
    }

    private function productStockTool(): Tool
    {
        return (new Tool)->as('get_product_stock')
            ->for('Get current stock levels for products. Use for questions about stock, inventory, availability, or product prices.')
            ->withStringParameter('search', 'Optional product name or barcode filter (leave empty for all)')
            ->using(function (string $search = '') {
                $query = Product::query()->withScopes(new Active);

                if ($search !== '') {
                    $query->withScopes(new ProductBySearch($search));
                }

                $products = $query->with('productType')->orderBy('name')->limit(50)->get();

                if ($products->isEmpty()) {
                    return 'No products found.';
                }

                return $products->map(fn ($p) => "- {$p->name} [{$p->productType?->name}]: stock={$p->stock}, min={$p->min_stock}, price={$p->price}")->join("\n");
            });
    }

    private function lowStockTool(): Tool
    {
        return (new Tool)->as('get_low_stock_products')
            ->for('Get products at or below minimum stock level. Use for low stock alerts or replenishment questions.')
            ->withStringParameter('search', 'Optional product name filter (leave empty for all)')
            ->using(function (string $search = '') {
                $query = Product::query()->withScopes(new Active)->withScopes(new BelowMinStock);

                if ($search !== '') {
                    $query->withScopes(new ProductBySearch($search));
                }

                $products = $query->orderBy('stock')->limit(50)->get();

                if ($products->isEmpty()) {
                    return 'No products below minimum stock. All good!';
                }

                return "Low stock products ({$products->count()}):\n"
                    .$products->map(fn ($p) => "- {$p->name}: stock={$p->stock} (min={$p->min_stock})")->join("\n");
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

                $composites = $query->with('products')->orderBy('name')->limit(30)->get();

                if ($composites->isEmpty()) {
                    return 'No composite products configured.';
                }

                return "Composite products ({$composites->count()}):\n"
                    .$composites->map(function ($cp) {
                        $components = $cp->products->map(fn ($p) => "{$p->pivot->quantity}x {$p->name}")->join(', ');

                        return "- {$cp->name} (price={$cp->price}): {$components}";
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

                $promotions = $query->with('products')->orderBy('name')->limit(30)->get();

                if ($promotions->isEmpty()) {
                    return 'No active promotions found.';
                }

                return "Active promotions ({$promotions->count()}):\n"
                    .$promotions->map(function ($promo) {
                        $items = $promo->products->map(fn ($p) => "{$p->pivot->quantity}x {$p->name}")->join(', ');
                        $dates = $promo->starts_at ? " [{$promo->starts_at} → {$promo->ends_at}]" : '';

                        return "- {$promo->name} (price={$promo->price}){$dates}: {$items}";
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
                $query = Reception::query()->with(['supplier', 'items.product'])->latest();

                if ($search !== '') {
                    $query->withScopes(new ReceptionBySearch($search));
                }

                $receptions = $query->limit((int) min((int) $limit, 50))->get();

                if ($receptions->isEmpty()) {
                    return 'No receptions found.';
                }

                return $receptions->map(function ($r) {
                    $items = $r->items->map(fn ($i) => "{$i->quantity}x {$i->product?->name}")->join(', ');

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
                    ->with(['pointOfSale', 'movements.cashMovementType']);

                if ($point_of_sale !== '') {
                    $query->whereHas('pointOfSale', fn ($q) => $q->where('name', 'like', "%{$point_of_sale}%"));
                }

                $cashes = $query->get();

                if ($cashes->isEmpty()) {
                    return 'No open daily cash registers at the moment.';
                }

                return $cashes->map(function ($c) {
                    $income = $c->movements->where('cashMovementType.is_income', true)->sum('amount');
                    $expenses = $c->movements->where('cashMovementType.is_income', false)->sum('amount');

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
                    $query->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"));
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

                $clients = $query->orderBy('name')->limit(20)->get();

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
}
