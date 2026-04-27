<?php

namespace Tests\Feature\Controllers;

use App\Models\Client;
use App\Models\Courier;
use App\Models\DailyCash;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\ProductPresentation;
use App\Models\Reception;
use App\Models\Sale;
use App\Models\SaleState;
use App\Models\Supplier;
use Tests\Feature\TenantFeatureTestCase;

class ReportControllerTest extends TenantFeatureTestCase
{
    // ── AUTH ──────────────────────────────────────────────────────────────────

    public function test_all_report_endpoints_require_authentication(): void
    {
        $endpoints = [
            '/api/reports/sales',
            '/api/reports/products',
            '/api/reports/payments',
            '/api/reports/inventory',
            '/api/reports/daily-cashes',
            '/api/reports/orders',
            '/api/reports/clients',
            '/api/reports/purchases',
        ];

        foreach ($endpoints as $endpoint) {
            $this->getJson($endpoint)->assertUnauthorized();
        }
    }

    // ── SALES ─────────────────────────────────────────────────────────────────

    public function test_sales_report_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/sales')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['date_from', 'date_to', 'total'],
                'kpis' => ['total_revenue', 'total_collected', 'total_discounts', 'count', 'avg_ticket'],
                'chart',
                'table',
                'filters' => ['clients', 'pointsOfSale', 'saleStates'],
            ]);
    }

    public function test_sales_report_kpis_reflect_created_sales(): void
    {
        Sale::factory()->count(3)->create(['total' => 500, 'discount_amount' => 0]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/sales')
            ->assertOk();

        $this->assertGreaterThanOrEqual(3, $response->json('kpis.count'));
        $this->assertGreaterThanOrEqual(1500, $response->json('kpis.total_revenue'));
    }

    public function test_sales_report_chart_fills_missing_days(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/sales?date_from=2026-01-01&date_to=2026-01-07')
            ->assertOk();

        $chart = $response->json('chart');
        $this->assertCount(7, $chart);
        $this->assertArrayHasKey('date', $chart[0]);
        $this->assertArrayHasKey('revenue', $chart[0]);
        $this->assertArrayHasKey('count', $chart[0]);
    }

    public function test_sales_report_filters_by_date_range(): void
    {
        Sale::factory()->create(['created_at' => '2025-01-15', 'total' => 999]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/sales?date_from=2026-01-01&date_to=2026-12-31')
            ->assertOk();

        $totals = collect($response->json('table'))->pluck('total');
        $this->assertNotContains(999.0, $totals->all());
    }

    public function test_sales_report_filters_by_client(): void
    {
        $pos = PointOfSale::factory()->create();
        $client = Client::factory()->create();
        Sale::factory()->create(['client_id' => $client->id, 'point_of_sale_id' => $pos->id, 'total' => 750]);
        Sale::factory()->create(['client_id' => Client::factory()->create()->id, 'point_of_sale_id' => $pos->id, 'total' => 250]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports/sales?client_id={$client->uuid}")
            ->assertOk();

        foreach ($response->json('table') as $row) {
            $this->assertSame($client->name, $row['client']);
        }
    }

    public function test_sales_report_avg_ticket_is_calculated(): void
    {
        Sale::factory()->count(2)->create(['total' => 200]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/sales')
            ->assertOk();

        $this->assertGreaterThan(0, $response->json('kpis.avg_ticket'));
    }

    public function test_sales_export_returns_xlsx(): void
    {
        Sale::factory()->count(2)->create();

        $this->actingAs($this->user, 'sanctum')
            ->get('/api/reports/sales/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── PRODUCTS ──────────────────────────────────────────────────────────────

    public function test_products_report_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/products')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['date_from', 'date_to', 'total'],
                'kpis' => ['total_revenue', 'total_units', 'unique_products', 'top_product'],
                'chart',
                'table',
                'filters' => ['productTypes'],
            ]);
    }

    public function test_products_report_returns_zero_kpis_with_no_data(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/products?date_from=2020-01-01&date_to=2020-01-02')
            ->assertOk();

        $this->assertEquals(0, $response->json('kpis.total_revenue'));
        $this->assertEquals(0, $response->json('kpis.unique_products'));
    }

    public function test_products_export_returns_xlsx(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->get('/api/reports/products/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── PAYMENTS ──────────────────────────────────────────────────────────────

    public function test_payments_report_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/payments')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['date_from', 'date_to', 'total'],
                'kpis' => ['total_amount', 'count', 'top_method', 'avg_amount'],
                'by_method',
                'chart',
                'table',
                'filters' => ['paymentMethods', 'pointsOfSale'],
            ]);
    }

    public function test_payments_report_aggregates_by_method(): void
    {
        $pm = PaymentMethod::factory()->create();
        Payment::factory()->count(3)->create([
            'payment_method_id' => $pm->id,
            'amount' => 100,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/payments')
            ->assertOk();

        $found = collect($response->json('by_method'))->firstWhere('name', $pm->name);

        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual(300, $found['total']);
        $this->assertGreaterThanOrEqual(3, $found['count']);
    }

    public function test_payments_report_sums_total_amount(): void
    {
        Payment::factory()->count(2)->create(['amount' => 200]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/payments')
            ->assertOk();

        $this->assertGreaterThanOrEqual(400, $response->json('kpis.total_amount'));
        $this->assertGreaterThanOrEqual(2, $response->json('kpis.count'));
    }

    public function test_payments_report_chart_fills_missing_days(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/payments?date_from=2026-03-01&date_to=2026-03-05')
            ->assertOk();

        $this->assertCount(5, $response->json('chart'));
    }

    public function test_payments_export_returns_xlsx(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->get('/api/reports/payments/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── INVENTORY ─────────────────────────────────────────────────────────────

    public function test_inventory_report_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/inventory')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['total'],
                'kpis' => ['total_items', 'low_stock_count', 'out_of_stock_count', 'ok_count'],
                'table',
                'filters' => ['productTypes'],
            ]);
    }

    public function test_inventory_report_classifies_stock_status(): void
    {
        ProductPresentation::factory()->create(['stock' => 50, 'min_stock' => 10, 'is_active' => true]);
        ProductPresentation::factory()->create(['stock' => 5, 'min_stock' => 10, 'is_active' => true]);
        ProductPresentation::factory()->create(['stock' => 0, 'min_stock' => 5, 'is_active' => true]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/inventory')
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, $response->json('kpis.ok_count'));
        $this->assertGreaterThanOrEqual(1, $response->json('kpis.low_stock_count'));
        $this->assertGreaterThanOrEqual(1, $response->json('kpis.out_of_stock_count'));
    }

    public function test_inventory_report_filters_out_of_stock(): void
    {
        ProductPresentation::factory()->create(['stock' => 0, 'min_stock' => 5, 'is_active' => true]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/inventory?stock_status=out')
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
        $this->assertGreaterThanOrEqual(1, $response->json('kpis.out_of_stock_count'));
    }

    public function test_inventory_report_filters_low_stock(): void
    {
        ProductPresentation::factory()->create(['stock' => 3, 'min_stock' => 1000, 'is_active' => true]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/inventory?stock_status=low')
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
        $this->assertGreaterThanOrEqual(1, $response->json('kpis.low_stock_count'));
    }

    public function test_inventory_report_excludes_inactive_presentations(): void
    {
        ProductPresentation::factory()->create(['stock' => 0, 'min_stock' => 5, 'is_active' => false]);

        $countBefore = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/inventory')
            ->json('meta.total');

        ProductPresentation::factory()->create(['stock' => 10, 'min_stock' => 5, 'is_active' => true]);

        $countAfter = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/inventory')
            ->json('meta.total');

        $this->assertGreaterThan($countBefore, $countAfter);
    }

    public function test_inventory_export_returns_xlsx(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->get('/api/reports/inventory/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── DAILY CASHES ──────────────────────────────────────────────────────────

    public function test_daily_cashes_report_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/daily-cashes')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['date_from', 'date_to', 'total'],
                'kpis' => ['count', 'closed_count', 'total_collected', 'total_income', 'total_expense'],
                'table',
                'filters' => ['pointsOfSale'],
            ]);
    }

    public function test_daily_cashes_report_counts_closed_correctly(): void
    {
        $pos = PointOfSale::factory()->create();
        DailyCash::factory()->count(2)->create(['point_of_sale_id' => $pos->id, 'is_closed' => true, 'opened_at' => now()->subDay()]);
        DailyCash::factory()->create(['point_of_sale_id' => $pos->id, 'is_closed' => false, 'opened_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/daily-cashes')
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, $response->json('kpis.closed_count'));
        $this->assertGreaterThanOrEqual(3, $response->json('kpis.count'));
    }

    public function test_daily_cashes_report_filters_by_point_of_sale(): void
    {
        $pos = PointOfSale::factory()->create();
        DailyCash::factory()->create(['point_of_sale_id' => $pos->id, 'opened_at' => now()]);
        DailyCash::factory()->create(['opened_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports/daily-cashes?point_of_sale_id={$pos->uuid}")
            ->assertOk();

        foreach ($response->json('table') as $row) {
            $this->assertSame($pos->name, $row['point_of_sale']);
        }
    }

    public function test_daily_cashes_table_rows_have_correct_status(): void
    {
        DailyCash::factory()->create(['is_closed' => true, 'opened_at' => now()]);
        DailyCash::factory()->create(['is_closed' => false, 'opened_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/daily-cashes')
            ->assertOk();

        $statuses = collect($response->json('table'))->pluck('status')->unique()->sort()->values();
        $this->assertContains('closed', $statuses->all());
        $this->assertContains('open', $statuses->all());
    }

    public function test_daily_cashes_export_returns_xlsx(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->get('/api/reports/daily-cashes/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── ORDERS ────────────────────────────────────────────────────────────────

    public function test_orders_report_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/orders')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['date_from', 'date_to', 'total'],
                'kpis' => ['count', 'total_revenue', 'avg_ticket', 'states_count'],
                'by_state',
                'table',
                'filters' => ['clients', 'orderStates', 'couriers'],
            ]);
    }

    public function test_orders_report_groups_by_state(): void
    {
        $state = OrderState::factory()->create();
        Order::factory()->count(3)->create(['order_state_id' => $state->id, 'total' => 100]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/orders')
            ->assertOk();

        $found = collect($response->json('by_state'))->firstWhere('name', $state->name);

        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual(3, $found['count']);
    }

    public function test_orders_report_filters_by_courier(): void
    {
        $courier = Courier::factory()->create();
        Order::factory()->count(2)->create(['courier_id' => $courier->id]);
        Order::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports/orders?courier_id={$courier->uuid}")
            ->assertOk();

        foreach ($response->json('table') as $row) {
            $this->assertSame($courier->name, $row['courier']);
        }
    }

    public function test_orders_report_kpis_sum_correctly(): void
    {
        Order::factory()->count(4)->create(['total' => 250]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/orders')
            ->assertOk();

        $this->assertGreaterThanOrEqual(4, $response->json('kpis.count'));
        $this->assertGreaterThanOrEqual(1000, $response->json('kpis.total_revenue'));
    }

    public function test_orders_export_returns_xlsx(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->get('/api/reports/orders/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── CLIENTS ───────────────────────────────────────────────────────────────

    public function test_clients_report_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/clients')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['date_from', 'date_to', 'total'],
                'kpis' => ['total_revenue', 'total_sales', 'active_clients', 'total_clients'],
                'chart',
                'table',
                'filters',
            ]);
    }

    public function test_clients_report_counts_all_clients(): void
    {
        Client::factory()->count(4)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/clients')
            ->assertOk();

        $this->assertGreaterThanOrEqual(4, $response->json('kpis.total_clients'));
    }

    public function test_clients_report_includes_sale_revenue_per_client(): void
    {
        $client = Client::factory()->create();
        $pos = PointOfSale::factory()->create();
        Sale::factory()->count(2)->create(['client_id' => $client->id, 'point_of_sale_id' => $pos->id, 'total' => 300]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/clients')
            ->assertOk();

        $found = collect($response->json('table'))->firstWhere('name', $client->name);

        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual(2, $found['sales_count']);
        $this->assertGreaterThanOrEqual(600, $found['total_revenue']);
    }

    public function test_clients_report_active_clients_only_counts_with_sales(): void
    {
        Client::factory()->count(3)->create();
        $client = Client::factory()->create();
        $pos = PointOfSale::factory()->create();
        Sale::factory()->create(['client_id' => $client->id, 'point_of_sale_id' => $pos->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/clients')
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, $response->json('kpis.active_clients'));
    }

    public function test_clients_export_returns_xlsx(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->get('/api/reports/clients/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── PURCHASES ─────────────────────────────────────────────────────────────

    public function test_purchases_report_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/purchases')
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['date_from', 'date_to', 'total'],
                'kpis' => ['count', 'total_cost', 'unique_suppliers', 'avg_reception'],
                'by_supplier',
                'table',
                'filters' => ['suppliers'],
            ]);
    }

    public function test_purchases_report_kpis_reflect_receptions(): void
    {
        $supplier = Supplier::factory()->create();
        Reception::factory()->count(3)->create([
            'supplier_id' => $supplier->id,
            'total' => 1000,
            'received_at' => now()->subDay()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/purchases')
            ->assertOk();

        $this->assertGreaterThanOrEqual(3, $response->json('kpis.count'));
        $this->assertGreaterThanOrEqual(3000, $response->json('kpis.total_cost'));
    }

    public function test_purchases_report_groups_by_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        Reception::factory()->count(2)->create([
            'supplier_id' => $supplier->id,
            'total' => 500,
            'received_at' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/purchases')
            ->assertOk();

        $found = collect($response->json('by_supplier'))->firstWhere('name', $supplier->name);

        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual(1000, $found['total']);
    }

    public function test_purchases_report_filters_by_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        Reception::factory()->create(['supplier_id' => $supplier->id, 'received_at' => now()->format('Y-m-d')]);
        Reception::factory()->create(['received_at' => now()->format('Y-m-d')]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports/purchases?supplier_id={$supplier->uuid}")
            ->assertOk();

        foreach ($response->json('table') as $row) {
            $this->assertSame($supplier->name, $row['supplier']);
        }
    }

    public function test_purchases_report_filters_by_date_range(): void
    {
        Reception::factory()->create(['total' => 9999, 'received_at' => '2020-06-01']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/purchases?date_from=2026-01-01&date_to=2026-12-31')
            ->assertOk();

        $totals = collect($response->json('table'))->pluck('total');
        $this->assertNotContains(9999.0, $totals->all());
    }

    public function test_purchases_export_returns_xlsx(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->get('/api/reports/purchases/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
