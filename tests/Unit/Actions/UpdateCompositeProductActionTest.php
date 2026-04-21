<?php

namespace Tests\Unit\Actions;

use App\Actions\UpdateCompositeProductAction;
use App\Models\CompositeProduct;
use App\Models\CompositeProductItem;
use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UpdateCompositeProductActionTest extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'in_ventra_testing')]);

        if (! self::$migrated) {
            Artisan::call('migrate:fresh', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            self::$migrated = true;
        }
    }

    private function action(): UpdateCompositeProductAction
    {
        return new UpdateCompositeProductAction;
    }

    public function test_updates_composite_product_data(): void
    {
        $compositeProduct = CompositeProduct::factory()->create(['name' => 'Old Name']);
        $product = Product::factory()->create();

        $updated = $this->action()->execute($compositeProduct, [
            'name' => 'New Name',
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 2],
            ],
        ]);

        $this->assertSame('New Name', $updated->name);
    }

    public function test_syncs_items_replacing_old_ones(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();
        $oldProduct = Product::factory()->create();
        CompositeProductItem::factory()->create([
            'composite_product_id' => $compositeProduct->id,
            'product_id' => $oldProduct->id,
        ]);

        $newProduct = Product::factory()->create();

        $updated = $this->action()->execute($compositeProduct, [
            'name' => $compositeProduct->name,
            'is_active' => true,
            'items' => [
                ['product_id' => $newProduct->uuid, 'quantity' => 4],
            ],
        ]);

        $this->assertCount(1, $updated->items);
        $this->assertEquals($newProduct->id, $updated->items->first()->product_id);
        $this->assertEquals(4, $updated->items->first()->quantity);
    }

    public function test_returns_composite_product_with_relations_loaded(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();
        $product = Product::factory()->create();

        $updated = $this->action()->execute($compositeProduct, [
            'name' => $compositeProduct->name,
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($updated->relationLoaded('items'));
    }
}
