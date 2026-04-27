<?php

namespace Tests\Feature\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\ProductPresentation;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleState;
use Tests\Feature\TenantFeatureTestCase;

class DashboardControllerTest extends TenantFeatureTestCase
{
    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_it_returns_expected_structure(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'kpis' => [
                    'today_revenue',
                    'today_sales_count',
                    'today_collected',
                    'month_revenue',
                    'month_sales_count',
                    'active_orders',
                    'pending_quotes',
                    'total_clients',
                    'low_stock_count',
                    'open_cashes_count',
                ],
                'sales_chart',
                'payment_methods',
                'order_states',
                'open_cashes',
                'recent_sales',
                'top_products',
                'low_stock',
                'weekly_comparison',
            ]);
    }

    public function test_kpis_reflect_todays_sales(): void
    {
        Sale::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $this->assertGreaterThanOrEqual(3, $response->json('kpis.today_sales_count'));
    }

    public function test_kpis_reflect_client_count(): void
    {
        Client::factory()->count(2)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, $response->json('kpis.total_clients'));
    }

    public function test_kpis_reflect_pending_quotes(): void
    {
        Quote::factory()->count(2)->create(['sale_id' => null]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, $response->json('kpis.pending_quotes'));
    }

    public function test_kpis_reflect_active_orders(): void
    {
        $activeState = OrderState::factory()->create(['is_final_state' => false]);
        Order::factory()->count(2)->create(['order_state_id' => $activeState->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, $response->json('kpis.active_orders'));
    }

    public function test_kpis_reflect_low_stock(): void
    {
        ProductPresentation::factory()->create(['stock' => 2, 'min_stock' => 10, 'is_active' => true]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, $response->json('kpis.low_stock_count'));
    }

    public function test_sales_chart_returns_30_days(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $chart = $response->json('sales_chart');
        $this->assertCount(30, $chart);
        $this->assertArrayHasKey('date', $chart[0]);
        $this->assertArrayHasKey('revenue', $chart[0]);
        $this->assertArrayHasKey('count', $chart[0]);
    }

    public function test_recent_sales_returns_at_most_8(): void
    {
        Sale::factory()->count(12)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $this->assertLessThanOrEqual(8, count($response->json('recent_sales')));
    }

    public function test_low_stock_returns_at_most_5(): void
    {
        ProductPresentation::factory()->count(10)->create(['stock' => 0, 'min_stock' => 5, 'is_active' => true]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $this->assertLessThanOrEqual(5, count($response->json('low_stock')));
    }

    public function test_payment_methods_aggregates_by_method(): void
    {
        $pm = PaymentMethod::factory()->create();
        $state = SaleState::factory()->create(['is_default' => true]);
        $pos = PointOfSale::factory()->create();
        $pp = ProductPresentation::factory()->create(['stock' => 100]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', [
                'point_of_sale_id' => $pos->uuid,
                'sale_state_id' => $state->uuid,
                'items' => [[
                    'product_presentation_id' => $pp->uuid,
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100,
                ]],
                'payments' => [['payment_method_id' => $pm->uuid, 'amount' => 100]],
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk();

        $methods = collect($response->json('payment_methods'));
        $found = $methods->firstWhere('name', $pm->name);
        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual(100, $found['total']);
    }
}
