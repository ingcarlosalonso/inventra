<?php

namespace Tests\Unit\Actions;

use App\Actions\StoreProductAction;
use App\Models\Presentation;
use App\Models\ProductType;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StoreProductActionTest extends TestCase
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

    private function action(): StoreProductAction
    {
        return new StoreProductAction;
    }

    public function test_creates_product_with_presentations(): void
    {
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $product = $this->action()->execute([
            'name' => 'Test Product',
            'product_type_id' => $productType->uuid,
            'is_active' => true,
            'presentations' => [[
                'presentation_id' => $presentation->uuid,
                'price' => 100.00,
                'min_stock' => 5,
                'stock' => 50,
            ]],
        ]);

        $this->assertSame('Test Product', $product->name);
        $this->assertCount(1, $product->productPresentations);
        $this->assertEquals(50, $product->productPresentations->first()->stock);
        $this->assertEquals(100.00, $product->productPresentations->first()->price);
    }

    public function test_creates_product_with_barcodes(): void
    {
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $product = $this->action()->execute([
            'name' => 'Barcode Product',
            'product_type_id' => $productType->uuid,
            'is_active' => true,
            'barcodes' => ['7791234567890', '7799876543210'],
            'presentations' => [[
                'presentation_id' => $presentation->uuid,
                'price' => 50,
                'min_stock' => 0,
            ]],
        ]);

        $this->assertCount(2, $product->barcodes);
        $this->assertContains('7791234567890', $product->barcodes->pluck('barcode')->all());
    }

    public function test_creates_product_without_optional_currency(): void
    {
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $product = $this->action()->execute([
            'name' => 'No Currency Product',
            'product_type_id' => $productType->uuid,
            'is_active' => true,
            'presentations' => [[
                'presentation_id' => $presentation->uuid,
                'price' => 75,
                'min_stock' => 2,
            ]],
        ]);

        $this->assertNull($product->currency_id);
        $this->assertSame('No Currency Product', $product->name);
    }

    public function test_returns_product_with_relations_loaded(): void
    {
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $product = $this->action()->execute([
            'name' => 'Relations Product',
            'product_type_id' => $productType->uuid,
            'is_active' => true,
            'presentations' => [[
                'presentation_id' => $presentation->uuid,
                'price' => 200,
                'min_stock' => 1,
            ]],
        ]);

        $this->assertTrue($product->relationLoaded('productType'));
        $this->assertTrue($product->relationLoaded('barcodes'));
        $this->assertTrue($product->relationLoaded('productPresentations'));
    }
}
