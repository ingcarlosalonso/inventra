<?php

namespace Tests\Unit\Actions;

use App\Actions\UpdatePromotionAction;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionItem;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UpdatePromotionActionTest extends TestCase
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

    private function action(): UpdatePromotionAction
    {
        return new UpdatePromotionAction;
    }

    public function test_updates_promotion_data(): void
    {
        $promotion = Promotion::factory()->create(['name' => 'Old Promo']);
        $product = Product::factory()->create();

        $updated = $this->action()->execute($promotion, [
            'name' => 'Updated Promo',
            'sale_price' => 299.00,
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 1],
            ],
        ]);

        $this->assertSame('Updated Promo', $updated->name);
    }

    public function test_syncs_items_replacing_old_ones(): void
    {
        $promotion = Promotion::factory()->create();
        $oldProduct = Product::factory()->create();
        PromotionItem::factory()->create([
            'promotion_id' => $promotion->id,
            'product_id' => $oldProduct->id,
        ]);

        $newProduct = Product::factory()->create();

        $updated = $this->action()->execute($promotion, [
            'name' => $promotion->name,
            'is_active' => true,
            'items' => [
                ['product_id' => $newProduct->uuid, 'quantity' => 3],
            ],
        ]);

        $this->assertCount(1, $updated->items);
        $this->assertEquals($newProduct->id, $updated->items->first()->product_id);
        $this->assertEquals(3, $updated->items->first()->quantity);
    }

    public function test_returns_promotion_with_relations_loaded(): void
    {
        $promotion = Promotion::factory()->create();
        $product = Product::factory()->create();

        $updated = $this->action()->execute($promotion, [
            'name' => $promotion->name,
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($updated->relationLoaded('items'));
    }
}
