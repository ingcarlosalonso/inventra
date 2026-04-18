<?php

namespace Tests\Unit\Services;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'inventra_testing')]);

        if (! self::$migrated) {
            Artisan::call('migrate:fresh', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            self::$migrated = true;
        }
    }

    private function service(): DashboardService
    {
        return new DashboardService;
    }

    public function test_get_data_returns_all_expected_keys(): void
    {
        $data = $this->service()->getData();

        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('sales_chart', $data);
        $this->assertArrayHasKey('payment_methods', $data);
        $this->assertArrayHasKey('order_states', $data);
        $this->assertArrayHasKey('open_cashes', $data);
        $this->assertArrayHasKey('recent_sales', $data);
        $this->assertArrayHasKey('top_products', $data);
        $this->assertArrayHasKey('low_stock', $data);
        $this->assertArrayHasKey('weekly_comparison', $data);
    }

    public function test_kpis_have_expected_keys(): void
    {
        $kpis = $this->service()->getData()['kpis'];

        $this->assertArrayHasKey('today_revenue', $kpis);
        $this->assertArrayHasKey('today_sales_count', $kpis);
        $this->assertArrayHasKey('today_collected', $kpis);
        $this->assertArrayHasKey('month_revenue', $kpis);
        $this->assertArrayHasKey('month_sales_count', $kpis);
        $this->assertArrayHasKey('active_orders', $kpis);
        $this->assertArrayHasKey('pending_quotes', $kpis);
        $this->assertArrayHasKey('total_clients', $kpis);
        $this->assertArrayHasKey('low_stock_count', $kpis);
        $this->assertArrayHasKey('open_cashes_count', $kpis);
    }

    public function test_sales_chart_returns_30_days(): void
    {
        $chart = $this->service()->getData()['sales_chart'];

        $this->assertCount(30, $chart);
        $this->assertArrayHasKey('date', $chart[0]);
        $this->assertArrayHasKey('revenue', $chart[0]);
        $this->assertArrayHasKey('count', $chart[0]);
        $this->assertArrayHasKey('discounts', $chart[0]);
    }

    public function test_kpis_are_numeric(): void
    {
        $kpis = $this->service()->getData()['kpis'];

        $this->assertIsFloat($kpis['today_revenue']);
        $this->assertIsInt($kpis['today_sales_count']);
        $this->assertIsFloat($kpis['today_collected']);
        $this->assertIsInt($kpis['active_orders']);
    }
}
