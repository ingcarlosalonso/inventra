<?php

namespace Tests\Unit\Actions;

use App\Actions\StoreCompositeProductAction;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreCompositeProductActionTest extends TestCase
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

    private function action(): StoreCompositeProductAction
    {
        return new StoreCompositeProductAction;
    }

    public function test_creates_composite_product_with_items(): void
    {
        $product = Product::factory()->create();

        $compositeProduct = $this->action()->execute([
            'name' => 'Kit Test',
            'code' => 'KIT-001',
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 3],
            ],
        ]);

        $this->assertSame('Kit Test', $compositeProduct->name);
        $this->assertSame('KIT-001', $compositeProduct->code);
        $this->assertCount(1, $compositeProduct->items);
        $this->assertEquals(3, $compositeProduct->items->first()->quantity);
    }

    public function test_creates_composite_product_with_multiple_items(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $compositeProduct = $this->action()->execute([
            'name' => 'Kit Multi',
            'is_active' => true,
            'items' => [
                ['product_id' => $product1->uuid, 'quantity' => 2],
                ['product_id' => $product2->uuid, 'quantity' => 5],
            ],
        ]);

        $this->assertCount(2, $compositeProduct->items);
    }

    public function test_returns_composite_product_with_relations_loaded(): void
    {
        $product = Product::factory()->create();

        $compositeProduct = $this->action()->execute([
            'name' => 'Kit Relations',
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($compositeProduct->relationLoaded('items'));
    }
}
