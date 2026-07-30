<?php

namespace Tests\Unit\Actions\Assistant;

use App\Actions\Assistant\ResolvePointOfSaleMatch;
use App\Models\PointOfSale;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResolvePointOfSaleMatchTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
    }

    public function test_blank_search_returns_all_active_points_of_sale(): void
    {
        $central = PointOfSale::factory()->create(['name' => 'Caja Central']);
        $norte = PointOfSale::factory()->create(['name' => 'Sucursal Norte']);
        $inactive = PointOfSale::factory()->create(['name' => 'Inactiva', 'is_active' => false]);

        $result = (new ResolvePointOfSaleMatch)->execute('');

        $this->assertTrue($result->pluck('id')->contains($central->id));
        $this->assertTrue($result->pluck('id')->contains($norte->id));
        $this->assertFalse($result->pluck('id')->contains($inactive->id));
    }

    public function test_search_filters_by_name(): void
    {
        $pos = PointOfSale::factory()->create(['name' => 'Caja Central']);
        PointOfSale::factory()->create(['name' => 'Sucursal Norte']);

        $result = (new ResolvePointOfSaleMatch)->execute('Central');

        $this->assertCount(1, $result);
        $this->assertSame($pos->id, $result->first()->id);
    }

    public function test_excludes_inactive_points_of_sale_from_search(): void
    {
        PointOfSale::factory()->create(['name' => 'Vieja', 'is_active' => false]);

        $result = (new ResolvePointOfSaleMatch)->execute('Vieja');

        $this->assertCount(0, $result);
    }
}
