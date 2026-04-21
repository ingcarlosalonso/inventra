<?php

namespace Tests\Unit\Actions;

use App\Actions\StorePromotionAction;
use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StorePromotionActionTest extends TestCase
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

    private function action(): StorePromotionAction
    {
        return new StorePromotionAction;
    }

    public function test_creates_promotion_with_items(): void
    {
        $product = Product::factory()->create();

        $promotion = $this->action()->execute([
            'name' => '2x1 Verano',
            'code' => 'PROMO-001',
            'sale_price' => 199.99,
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 2],
            ],
        ]);

        $this->assertSame('2x1 Verano', $promotion->name);
        $this->assertSame('PROMO-001', $promotion->code);
        $this->assertCount(1, $promotion->items);
        $this->assertEquals(2, $promotion->items->first()->quantity);
    }

    public function test_creates_promotion_without_sale_price(): void
    {
        $product = Product::factory()->create();

        $promotion = $this->action()->execute([
            'name' => 'Promo Sin Precio',
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 1],
            ],
        ]);

        $this->assertNull($promotion->sale_price);
    }

    public function test_returns_promotion_with_relations_loaded(): void
    {
        $product = Product::factory()->create();

        $promotion = $this->action()->execute([
            'name' => 'Relations Promo',
            'is_active' => true,
            'items' => [
                ['product_id' => $product->uuid, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($promotion->relationLoaded('items'));
    }
}
