<?php

namespace Tests\Unit\Services;

use App\Actions\Assistant\ResolveClientMatch;
use App\Actions\Assistant\ResolvePaymentMethodMatch;
use App\Actions\Assistant\ResolvePointOfSaleMatch;
use App\Actions\Assistant\ResolveSaleableItemMatches;
use App\Actions\BuildSaleItemsData;
use App\Models\CashMovement;
use App\Models\CashMovementType;
use App\Models\Client;
use App\Models\CompositeProduct;
use App\Models\CompositeProductItem;
use App\Models\Courier;
use App\Models\DailyCash;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductMovement;
use App\Models\ProductMovementType;
use App\Models\ProductPresentation;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Models\Quote;
use App\Models\Reception;
use App\Models\ReceptionItem;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleState;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AssistantService;
use App\Services\ProcessOrderService;
use App\Services\ProcessQuoteService;
use App\Services\ProcessSaleService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Tool;
use ReflectionClass;
use Tests\TestCase;

class AssistantServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['tenant'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'in_ventra_testing')]);
        DB::purge('tenant');
        DB::connection('tenant')->beginTransaction();

        self::migrateTenantDb();

        (new PermissionSeeder)->run();
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
    }

    private function service(): AssistantService
    {
        return new AssistantService(
            new ResolveClientMatch,
            new ResolveSaleableItemMatches,
            new ResolvePaymentMethodMatch,
            new ResolvePointOfSaleMatch,
            new BuildSaleItemsData,
            new ProcessSaleService(new BuildSaleItemsData),
            new ProcessOrderService(new BuildSaleItemsData),
            new ProcessQuoteService(new BuildSaleItemsData),
        );
    }

    private function tool(string $name, ?User $user = null, bool $draftAlreadyShown = false): Tool
    {
        $service = $this->service();
        $ref = new ReflectionClass($service);
        $tools = $ref->getMethod('tools')->invoke($service, $user ?? User::factory()->create(), $draftAlreadyShown);

        foreach ($tools as $tool) {
            if ($tool->name() === $name) {
                return $tool;
            }
        }

        throw new \RuntimeException("Tool [{$name}] not found.");
    }

    private function userWithPermissions(string|array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function presentationFor(Product $product, array $attributes = []): ProductPresentation
    {
        return ProductPresentation::factory()->create(array_merge([
            'product_id' => $product->id,
            'presentation_id' => Presentation::factory(),
        ], $attributes));
    }

    // ─── get_product_stock ──────────────────────────────────────────────────

    public function test_product_stock_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_product_stock')->handle(search: 'zzz_nonexistent_product_zzz');

        $this->assertSame('No products found.', $result);
    }

    public function test_product_stock_tool_reports_real_stock_min_and_price(): void
    {
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['stock' => 42, 'min_stock' => 5, 'price' => 999]);

        $result = $this->tool('get_product_stock')->handle(search: '');

        $this->assertStringContainsString('Coca-Cola 500ml', $result);
        $this->assertStringContainsString('stock=42', $result);
        $this->assertStringContainsString('min=5', $result);
        $this->assertStringContainsString('price=999', $result);
    }

    public function test_product_stock_tool_matches_multi_word_search_in_any_order(): void
    {
        $coca = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($coca);
        $sprite = Product::factory()->create(['name' => 'Sprite 500ml']);
        $this->presentationFor($sprite);

        $result = $this->tool('get_product_stock')->handle(search: '500ml coca');

        $this->assertStringContainsString('Coca-Cola', $result);
        $this->assertStringNotContainsString('Sprite', $result);
    }

    public function test_product_stock_tool_excludes_inactive_products(): void
    {
        $active = Product::factory()->create(['name' => 'Producto Activo']);
        $this->presentationFor($active);
        $inactive = Product::factory()->create(['name' => 'Producto Inactivo', 'is_active' => false]);
        $this->presentationFor($inactive);

        $result = $this->tool('get_product_stock')->handle(search: '');

        $this->assertStringContainsString('Producto Activo', $result);
        $this->assertStringNotContainsString('Producto Inactivo', $result);
    }

    public function test_product_stock_tool_excludes_inactive_presentations(): void
    {
        $product = Product::factory()->create(['name' => 'Multi Presentacion']);
        $this->presentationFor($product, ['is_active' => false]);

        $result = $this->tool('get_product_stock')->handle(search: 'Multi Presentacion');

        $this->assertSame('No products found.', $result);
    }

    // ─── get_low_stock_products ─────────────────────────────────────────────

    public function test_low_stock_tool_returns_message_when_none_below_minimum(): void
    {
        $product = Product::factory()->create();
        $this->presentationFor($product, ['stock' => 50, 'min_stock' => 5]);

        $result = $this->tool('get_low_stock_products')->handle(search: 'zzz_nonexistent_product_zzz');

        $this->assertSame('No products below minimum stock. All good!', $result);
    }

    public function test_low_stock_tool_lists_only_products_below_minimum(): void
    {
        $low = Product::factory()->create(['name' => 'Bajo Stock']);
        $this->presentationFor($low, ['stock' => 2, 'min_stock' => 10]);
        $ok = Product::factory()->create(['name' => 'Stock Normal']);
        $this->presentationFor($ok, ['stock' => 50, 'min_stock' => 5]);

        $result = $this->tool('get_low_stock_products')->handle(search: '');

        $this->assertStringContainsString('Bajo Stock', $result);
        $this->assertStringNotContainsString('Stock Normal', $result);
    }

    // ─── get_composite_products ─────────────────────────────────────────────

    public function test_composite_products_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_composite_products')->handle(search: 'zzz_nonexistent_kit_zzz');

        $this->assertSame('No composite products configured.', $result);
    }

    public function test_composite_products_tool_lists_components_with_quantities(): void
    {
        $composite = CompositeProduct::factory()->create(['name' => 'Combo Familiar']);
        $component = Product::factory()->create(['name' => 'Gaseosa']);
        CompositeProductItem::factory()->create([
            'composite_product_id' => $composite->id,
            'product_id' => $component->id,
            'quantity' => 3,
        ]);

        $result = $this->tool('get_composite_products')->handle(search: '');

        $this->assertStringContainsString('Combo Familiar', $result);
        $this->assertStringContainsString('3x Gaseosa', $result);
    }

    // ─── get_promotions ──────────────────────────────────────────────────────

    public function test_promotions_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_promotions')->handle(search: 'zzz_nonexistent_promo_zzz');

        $this->assertSame('No active promotions found.', $result);
    }

    public function test_promotions_tool_lists_items_and_price(): void
    {
        $promotion = Promotion::factory()->create(['name' => '2x1 Verano', 'sale_price' => 150]);
        $product = Product::factory()->create(['name' => 'Helado']);
        PromotionItem::factory()->create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $result = $this->tool('get_promotions')->handle(search: '');

        $this->assertStringContainsString('2x1 Verano', $result);
        $this->assertStringContainsString('price=150', $result);
        $this->assertStringContainsString('2x Helado', $result);
    }

    // ─── get_sales_summary ───────────────────────────────────────────────────

    public function test_sales_summary_tool_returns_message_when_no_sales(): void
    {
        $result = $this->tool('get_sales_summary')->handle(from: '2026-01-01', to: '2026-01-31');

        $this->assertStringContainsString('No sales found', $result);
    }

    public function test_sales_summary_tool_computes_totals(): void
    {
        Sale::factory()->create(['total' => 100, 'created_at' => '2026-01-15']);
        Sale::factory()->create(['total' => 200, 'created_at' => '2026-01-16']);

        $result = $this->tool('get_sales_summary')->handle(from: '2026-01-01', to: '2026-01-31');

        $this->assertStringContainsString('Count: 2', $result);
        $this->assertStringContainsString('300.00', $result);
        $this->assertStringContainsString('150.00', $result);
    }

    // ─── get_recent_sales ────────────────────────────────────────────────────

    public function test_recent_sales_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_recent_sales')->handle(search: 'zzz_nonexistent_client_zzz', limit: '10');

        $this->assertSame('No sales found.', $result);
    }

    public function test_recent_sales_tool_lists_client_and_state(): void
    {
        $client = Client::factory()->create(['first_name' => 'Ana', 'last_name' => 'Gomez']);
        $state = SaleState::factory()->create(['name' => 'Pagada']);
        Sale::factory()->create(['client_id' => $client->id, 'sale_state_id' => $state->id, 'total' => 500]);

        $result = $this->tool('get_recent_sales')->handle(search: '', limit: '10');

        $this->assertStringContainsString('Ana Gomez', $result);
        $this->assertStringContainsString('Pagada', $result);
    }

    // ─── get_recent_orders ───────────────────────────────────────────────────

    public function test_recent_orders_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_recent_orders')->handle(search: 'zzz_nonexistent_client_zzz', state: '', limit: '10');

        $this->assertSame('No orders found.', $result);
    }

    public function test_recent_orders_tool_lists_client_state_and_courier(): void
    {
        $client = Client::factory()->create(['first_name' => 'Luis', 'last_name' => 'Diaz']);
        $state = OrderState::factory()->create(['name' => 'En camino']);
        $courier = Courier::factory()->create(['name' => 'Pedro Flete']);
        Order::factory()->create([
            'client_id' => $client->id,
            'order_state_id' => $state->id,
            'courier_id' => $courier->id,
        ]);

        $result = $this->tool('get_recent_orders')->handle(search: '', state: '', limit: '10');

        $this->assertStringContainsString('Luis Diaz', $result);
        $this->assertStringContainsString('En camino', $result);
        $this->assertStringContainsString('Pedro Flete', $result);
    }

    // ─── get_recent_quotes ───────────────────────────────────────────────────

    public function test_recent_quotes_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_recent_quotes')->handle(search: 'zzz_nonexistent_client_zzz', limit: '10');

        $this->assertSame('No quotes found.', $result);
    }

    public function test_recent_quotes_tool_lists_client_and_total(): void
    {
        $client = Client::factory()->create(['first_name' => 'Marta', 'last_name' => 'Lopez']);
        Quote::factory()->create(['client_id' => $client->id, 'total' => 750]);

        $result = $this->tool('get_recent_quotes')->handle(search: '', limit: '10');

        $this->assertStringContainsString('Marta Lopez', $result);
        $this->assertStringContainsString('total=750', $result);
    }

    // ─── get_recent_receptions ───────────────────────────────────────────────

    public function test_recent_receptions_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_recent_receptions')->handle(search: 'zzz_nonexistent_supplier_zzz', limit: '10');

        $this->assertSame('No receptions found.', $result);
    }

    public function test_recent_receptions_tool_lists_supplier_and_items(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Distribuidora Sur']);
        $reception = Reception::factory()->create(['supplier_id' => $supplier->id, 'total' => 1000]);
        $product = Product::factory()->create(['name' => 'Yerba']);
        $presentation = $this->presentationFor($product);
        ReceptionItem::factory()->create([
            'reception_id' => $reception->id,
            'product_presentation_id' => $presentation->id,
            'quantity' => 5,
        ]);

        $result = $this->tool('get_recent_receptions')->handle(search: '', limit: '10');

        $this->assertStringContainsString('Distribuidora Sur', $result);
        $this->assertStringContainsString('Yerba', $result);
    }

    // ─── get_daily_cash_status ───────────────────────────────────────────────

    public function test_daily_cash_status_tool_returns_message_when_none_open(): void
    {
        $result = $this->tool('get_daily_cash_status')->handle(point_of_sale: 'zzz_nonexistent_pos_zzz');

        $this->assertSame('No open daily cash registers at the moment.', $result);
    }

    public function test_daily_cash_status_tool_computes_income_and_expenses(): void
    {
        $pos = PointOfSale::factory()->create(['name' => 'Caja Central']);
        $dailyCash = DailyCash::factory()->create([
            'point_of_sale_id' => $pos->id,
            'opening_balance' => 1000,
            'is_closed' => false,
        ]);
        CashMovement::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'amount' => 300,
            'cash_movement_type_id' => CashMovementType::factory()->create(['is_income' => true]),
        ]);
        CashMovement::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'amount' => 100,
            'cash_movement_type_id' => CashMovementType::factory()->create(['is_income' => false]),
        ]);

        $result = $this->tool('get_daily_cash_status')->handle(point_of_sale: '');

        $this->assertStringContainsString('Caja Central', $result);
        $this->assertStringContainsString('opening=1000', $result);
        $this->assertStringContainsString('income=300', $result);
        $this->assertStringContainsString('expenses=100', $result);
    }

    // ─── get_product_movements ───────────────────────────────────────────────

    public function test_product_movements_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_product_movements')->handle(search: 'zzz_nonexistent_product_zzz', limit: '15');

        $this->assertSame('No product movements found.', $result);
    }

    public function test_product_movements_tool_lists_product_type_and_user(): void
    {
        $user = User::factory()->create(['name' => 'Operador Uno']);
        $product = Product::factory()->create(['name' => 'Aceite']);
        $presentation = $this->presentationFor($product);
        $movementType = ProductMovementType::factory()->create(['name' => 'Ajuste']);
        ProductMovement::create([
            'product_id' => $product->id,
            'product_presentation_id' => $presentation->id,
            'product_movement_type_id' => $movementType->id,
            'user_id' => $user->id,
            'quantity' => 4,
        ]);

        $result = $this->tool('get_product_movements')->handle(search: '', limit: '15');

        $this->assertStringContainsString('Aceite', $result);
        $this->assertStringContainsString('Ajuste', $result);
        $this->assertStringContainsString('Operador Uno', $result);
    }

    // ─── get_top_selling_products ────────────────────────────────────────────

    public function test_top_selling_products_tool_returns_message_when_no_sales(): void
    {
        $result = $this->tool('get_top_selling_products')->handle(from: '2026-01-01', to: '2026-01-31', limit: '10');

        $this->assertStringContainsString('No sales found', $result);
    }

    public function test_top_selling_products_tool_ranks_by_revenue(): void
    {
        $product = Product::factory()->create(['name' => 'Producto Estrella']);
        $presentation = $this->presentationFor($product);
        $sale = Sale::factory()->create(['created_at' => '2026-01-10']);
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_presentation_id' => $presentation->id,
            'quantity' => 10,
            'total' => 1000,
        ]);

        $result = $this->tool('get_top_selling_products')->handle(from: '2026-01-01', to: '2026-01-31', limit: '10');

        $this->assertStringContainsString('Producto Estrella', $result);
        $this->assertStringContainsString('revenue=1,000.00', $result);
    }

    // ─── get_clients ─────────────────────────────────────────────────────────

    public function test_clients_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_clients')->handle(search: 'zzz_nonexistent_client_zzz');

        $this->assertSame('No clients found.', $result);
    }

    public function test_clients_tool_orders_alphabetically_without_error(): void
    {
        Client::factory()->create(['first_name' => 'Beto', 'last_name' => 'Zeta']);
        Client::factory()->create(['first_name' => 'Ana', 'last_name' => 'Alfa']);

        $result = $this->tool('get_clients')->handle(search: '');

        $this->assertStringContainsString('Ana Alfa', $result);
        $this->assertStringContainsString('Beto Zeta', $result);
        $this->assertTrue(strpos($result, 'Ana Alfa') < strpos($result, 'Beto Zeta'));
    }

    public function test_clients_tool_excludes_inactive_clients(): void
    {
        Client::factory()->create(['first_name' => 'Activo', 'last_name' => 'Cliente']);
        Client::factory()->create(['first_name' => 'Inactivo', 'last_name' => 'Cliente', 'is_active' => false]);

        $result = $this->tool('get_clients')->handle(search: '');

        $this->assertStringContainsString('Activo Cliente', $result);
        $this->assertStringNotContainsString('Inactivo Cliente', $result);
    }

    // ─── get_suppliers ───────────────────────────────────────────────────────

    public function test_suppliers_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_suppliers')->handle(search: 'zzz_nonexistent_supplier_zzz');

        $this->assertSame('No suppliers found.', $result);
    }

    public function test_suppliers_tool_lists_contact_and_phone(): void
    {
        Supplier::factory()->create(['name' => 'Insumos SA', 'contact_name' => 'Jorge', 'phone' => '123456']);

        $result = $this->tool('get_suppliers')->handle(search: '');

        $this->assertStringContainsString('Insumos SA', $result);
        $this->assertStringContainsString('Jorge', $result);
        $this->assertStringContainsString('123456', $result);
    }

    // ─── get_users ───────────────────────────────────────────────────────────

    public function test_users_tool_returns_message_when_no_matches(): void
    {
        $result = $this->tool('get_users')->handle(search: 'zzz_nonexistent_user_zzz');

        $this->assertSame('No users found.', $result);
    }

    public function test_users_tool_lists_roles(): void
    {
        $user = User::factory()->create(['name' => 'Admin Uno']);
        $role = Role::factory()->create(['name' => 'Administrador']);
        $user->assignRole($role);

        $result = $this->tool('get_users')->handle(search: '');

        $this->assertStringContainsString('Admin Uno', $result);
        $this->assertStringContainsString('Administrador', $result);
    }

    // ─── tenant isolation ────────────────────────────────────────────────────

    public function test_all_tools_are_registered(): void
    {
        $service = $this->service();
        $ref = new ReflectionClass($service);
        $tools = $ref->getMethod('tools')->invoke($service, User::factory()->create());

        $names = array_map(fn (Tool $tool) => $tool->name(), $tools);

        $this->assertSame([
            'get_product_stock',
            'get_low_stock_products',
            'get_composite_products',
            'get_promotions',
            'get_sales_summary',
            'get_recent_sales',
            'get_recent_orders',
            'get_recent_quotes',
            'get_recent_receptions',
            'get_daily_cash_status',
            'get_product_movements',
            'get_top_selling_products',
            'get_clients',
            'get_suppliers',
            'get_users',
            'create_sale',
            'create_order',
            'create_quote',
        ], $names);
    }

    // ─── create_sale ─────────────────────────────────────────────────────────

    public function test_create_sale_denies_without_permission(): void
    {
        $user = User::factory()->create();
        $pos = PointOfSale::factory()->create();
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100]);

        $result = $this->tool('create_sale', $user)->handle(
            confirm: true,
            items: [['search' => 'Coca-Cola', 'quantity' => 1]],
        );

        $this->assertStringContainsString('PERMISSION_DENIED', $result);
        $this->assertSame(0, Sale::count());
    }

    public function test_create_sale_reports_ambiguous_client(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_sales');
        Client::factory()->create(['first_name' => 'Juan', 'last_name' => 'Perez']);
        Client::factory()->create(['first_name' => 'Juan', 'last_name' => 'Gomez']);
        PointOfSale::factory()->create();

        $result = $this->tool('create_sale', $user)->handle(
            confirm: false,
            client_search: 'Juan',
            items: [['search' => 'zzz', 'quantity' => 1]],
        );

        $this->assertStringContainsString('Multiple clients match', $result);
        $this->assertSame(0, Sale::count());
    }

    public function test_create_sale_reports_item_not_found(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_sales');
        PointOfSale::factory()->create();

        $result = $this->tool('create_sale', $user)->handle(
            confirm: false,
            items: [['search' => 'zzz_nonexistent_zzz', 'quantity' => 1]],
        );

        $this->assertStringContainsString('No product found matching', $result);
        $this->assertSame(0, Sale::count());
    }

    public function test_create_sale_requires_point_of_sale_when_none_exists(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_sales');
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100]);

        $result = $this->tool('create_sale', $user)->handle(
            confirm: false,
            items: [['search' => 'Coca-Cola', 'quantity' => 1]],
        );

        $this->assertStringContainsString('No active points of sale', $result);
        $this->assertSame(0, Sale::count());
    }

    public function test_create_sale_preview_does_not_persist_anything(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_sales');
        PointOfSale::factory()->create(['name' => 'Caja Central']);
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $pp = $this->presentationFor($product, ['price' => 100, 'stock' => 50]);

        $result = $this->tool('create_sale', $user)->handle(
            confirm: false,
            items: [['search' => 'Coca-Cola', 'quantity' => 3]],
        );

        $this->assertStringContainsString('PREVIEW ONLY', $result);
        $this->assertStringContainsString('3x Coca-Cola', $result);
        $this->assertStringContainsString('Total: 300.00', $result);
        $this->assertStringContainsString('remains as debt/credit', $result);
        $this->assertSame(0, Sale::count());
        $this->assertSame('50.000', $pp->fresh()->stock);
    }

    public function test_create_sale_confirm_creates_sale_decrements_stock_and_registers_payment(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_sales');
        PointOfSale::factory()->create(['name' => 'Caja Central']);
        SaleState::factory()->default()->create();
        $client = Client::factory()->create(['first_name' => 'Marcela', 'last_name' => 'Fernandez']);
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $pp = $this->presentationFor($product, ['price' => 100, 'stock' => 50]);
        $method = PaymentMethod::factory()->create(['name' => 'Efectivo']);

        $result = $this->tool('create_sale', $user, true)->handle(
            confirm: true,
            client_search: 'Marcela',
            items: [['search' => 'Coca-Cola', 'quantity' => 3]],
            payments: [['method_search' => 'Efectivo', 'amount' => 300]],
        );

        $this->assertStringContainsString('created successfully', $result);
        $this->assertStringContainsString('fully paid', $result);
        $this->assertSame(1, Sale::count());

        $sale = Sale::first();
        $this->assertSame($client->id, $sale->client_id);
        $this->assertSame('300.00', $sale->total);
        $this->assertSame('47.000', $pp->fresh()->stock);
        $this->assertSame(1, $sale->payments()->count());
        $this->assertSame($method->id, $sale->payments()->first()->payment_method_id);
    }

    public function test_create_sale_reports_insufficient_stock_without_persisting(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_sales');
        PointOfSale::factory()->create();
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100, 'stock' => 1]);

        $result = $this->tool('create_sale', $user, true)->handle(
            confirm: true,
            items: [['search' => 'Coca-Cola', 'quantity' => 10]],
        );

        $this->assertStringContainsString('Coca-Cola', $result);
        $this->assertSame(0, Sale::count());
    }

    public function test_create_sale_auto_selects_the_only_point_of_sale(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_sales');
        $pos = PointOfSale::factory()->create(['name' => 'Unica Caja']);
        SaleState::factory()->default()->create();
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100, 'stock' => 50]);

        $result = $this->tool('create_sale', $user, true)->handle(
            confirm: true,
            items: [['search' => 'Coca-Cola', 'quantity' => 1]],
        );

        $this->assertStringContainsString('created successfully', $result);
        $this->assertSame($pos->id, Sale::first()->point_of_sale_id);
    }

    // ─── create_order ────────────────────────────────────────────────────────

    public function test_create_order_denies_without_permission(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100]);

        $result = $this->tool('create_order', $user)->handle(
            confirm: true,
            items: [['search' => 'Coca-Cola', 'quantity' => 1]],
        );

        $this->assertStringContainsString('PERMISSION_DENIED', $result);
        $this->assertSame(0, Order::count());
    }

    public function test_create_order_allows_no_point_of_sale(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_orders');
        OrderState::factory()->default()->create();
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $pp = $this->presentationFor($product, ['price' => 100, 'stock' => 20]);

        $result = $this->tool('create_order', $user, true)->handle(
            confirm: true,
            items: [['search' => 'Coca-Cola', 'quantity' => 2]],
        );

        $this->assertStringContainsString('created successfully', $result);
        $this->assertSame(1, Order::count());
        $this->assertNull(Order::first()->point_of_sale_id);
        $this->assertSame('18.000', $pp->fresh()->stock);
    }

    public function test_create_order_reports_courier_not_found(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_orders');
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100]);

        $result = $this->tool('create_order', $user)->handle(
            confirm: false,
            courier_search: 'zzz_nonexistent_zzz',
            items: [['search' => 'Coca-Cola', 'quantity' => 1]],
        );

        $this->assertStringContainsString('No courier found matching', $result);
        $this->assertSame(0, Order::count());
    }

    public function test_create_order_preview_includes_courier_and_address(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_orders');
        Courier::factory()->create(['name' => 'Pedro Flete']);
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100]);

        $result = $this->tool('create_order', $user)->handle(
            confirm: false,
            courier_search: 'Pedro',
            address: 'Calle Falsa 123',
            items: [['search' => 'Coca-Cola', 'quantity' => 1]],
        );

        $this->assertStringContainsString('PREVIEW ONLY', $result);
        $this->assertStringContainsString('Courier: Pedro Flete', $result);
        $this->assertStringContainsString('Address: Calle Falsa 123', $result);
        $this->assertSame(0, Order::count());
    }

    // ─── create_quote ────────────────────────────────────────────────────────

    public function test_create_quote_denies_without_permission(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100]);

        $result = $this->tool('create_quote', $user)->handle(
            confirm: true,
            items: [['search' => 'Coca-Cola', 'quantity' => 1]],
        );

        $this->assertStringContainsString('PERMISSION_DENIED', $result);
        $this->assertSame(0, Quote::count());
    }

    public function test_create_quote_preview_has_no_payments_section(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_quotes');
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100]);

        $result = $this->tool('create_quote', $user)->handle(
            confirm: false,
            items: [['search' => 'Coca-Cola', 'quantity' => 2]],
        );

        $this->assertStringContainsString('PREVIEW ONLY', $result);
        $this->assertStringNotContainsString('Payments:', $result);
        $this->assertSame(0, Quote::count());
    }

    public function test_create_quote_confirm_creates_quote_without_touching_stock(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_quotes');
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $pp = $this->presentationFor($product, ['price' => 100, 'stock' => 50]);

        $result = $this->tool('create_quote', $user, true)->handle(
            confirm: true,
            items: [['search' => 'Coca-Cola', 'quantity' => 2]],
        );

        $this->assertStringContainsString('created successfully', $result);
        $this->assertSame(1, Quote::count());
        $this->assertSame('200.00', Quote::first()->total);
        $this->assertSame('50.000', $pp->fresh()->stock);
    }

    // ─── chat() model fallback ───────────────────────────────────────────────

    public function test_chat_uses_the_configured_model(): void
    {
        config(['assistant.model' => 'llama-3.3-70b-versatile', 'assistant.fallback_models' => []]);
        Prism::fake([TextResponseFake::make()->withText('¡Hola!')]);

        $reply = $this->service()->chat([['role' => 'user', 'content' => 'Hola']], User::factory()->create());

        $this->assertSame('¡Hola!', $reply);
    }

    public function test_chat_returns_a_friendly_translated_message_when_every_configured_model_fails(): void
    {
        config(['assistant.model' => 'llama-3.3-70b-versatile', 'assistant.fallback_models' => ['llama-3.1-8b-instant']]);
        app()->setLocale('es');
        // A queue with no entry at index 0 makes Prism's fake throw "Could not find a response"
        // on every attempt (the sequence counter never advances past the failing lookup), which
        // simulates every configured model failing regardless of how many are configured.
        Prism::fake([1 => TextResponseFake::make()->withText('unused')]);

        $reply = $this->service()->chat([['role' => 'user', 'content' => 'Hola']], User::factory()->create());

        $this->assertSame(__('assistant.unavailable'), $reply);
        $this->assertNotEmpty($reply);
    }

    // ─── two-turn confirmation guard ─────────────────────────────────────────
    //
    // A model can call a tool with confirm=false and, within that same turn, call it again with
    // confirm=true before the human ever sees the draft — that would silently defeat the whole
    // point of the preview step. These tests cover the structural guard for that: confirm=true is
    // only honoured when the assistant's immediately preceding reply actually was a shown draft.

    public function test_create_sale_confirm_is_rejected_without_a_prior_draft_in_history(): void
    {
        $user = $this->userWithPermissions('create_edit_delete_sales');
        PointOfSale::factory()->create();
        SaleState::factory()->default()->create();
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $this->presentationFor($product, ['price' => 100, 'stock' => 50]);

        $result = $this->tool('create_sale', $user, draftAlreadyShown: false)->handle(
            confirm: true,
            items: [['search' => 'Coca-Cola', 'quantity' => 1]],
        );

        $this->assertStringContainsString('confirm=false first', $result);
        $this->assertSame(0, Sale::count());
    }

    public function test_chat_appends_a_stable_marker_to_draft_replies_and_detects_it_on_the_next_turn(): void
    {
        $service = $this->service();
        $ref = new ReflectionClass($service);
        $marker = $ref->getReflectionConstant('DRAFT_MARKER')->getValue();

        $priorReplyWasADraft = $ref->getMethod('priorReplyWasADraft');

        $this->assertFalse($priorReplyWasADraft->invoke($service, [
            ['role' => 'user', 'content' => 'Creame una venta'],
        ]));

        $this->assertFalse($priorReplyWasADraft->invoke($service, [
            ['role' => 'user', 'content' => 'Creame una venta'],
            ['role' => 'assistant', 'content' => 'Venta #12 creada con éxito.'],
            ['role' => 'user', 'content' => 'Confirmá'],
        ]));

        $this->assertTrue($priorReplyWasADraft->invoke($service, [
            ['role' => 'user', 'content' => 'Creame una venta'],
            ['role' => 'assistant', 'content' => "Total: 100.\n\n{$marker}"],
            ['role' => 'user', 'content' => 'Confirmá'],
        ]));
    }
}
