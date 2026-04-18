<?php

namespace Tests\Unit\Actions;

use App\Actions\UpdateProductAction;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\ProductType;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UpdateProductActionTest extends TestCase
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

    private function action(): UpdateProductAction
    {
        return new UpdateProductAction;
    }

    public function test_updates_product_name(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $updated = $this->action()->execute($product, [
            'name' => 'New Name',
            'product_type_id' => $productType->uuid,
            'is_active' => true,
            'presentations' => [[
                'presentation_id' => $presentation->uuid,
                'price' => 100,
                'min_stock' => 5,
            ]],
        ]);

        $this->assertSame('New Name', $updated->name);
    }

    public function test_syncs_presentations_on_update(): void
    {
        $product = Product::factory()->create();
        $oldPresentation = Presentation::factory()->create();
        ProductPresentation::factory()->create([
            'product_id' => $product->id,
            'presentation_id' => $oldPresentation->id,
        ]);

        $newPresentation = Presentation::factory()->create();
        $productType = ProductType::factory()->create();

        $this->action()->execute($product, [
            'name' => $product->name,
            'product_type_id' => $productType->uuid,
            'is_active' => true,
            'presentations' => [[
                'presentation_id' => $newPresentation->uuid,
                'price' => 200,
                'min_stock' => 10,
            ]],
        ]);

        $product->refresh();
        $this->assertCount(1, $product->productPresentations);
        $this->assertEquals($newPresentation->id, $product->productPresentations->first()->presentation_id);
    }

    public function test_replaces_barcodes_on_update(): void
    {
        $product = Product::factory()->create();
        $product->barcodes()->create(['barcode' => 'OLD-123']);

        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $this->action()->execute($product, [
            'name' => $product->name,
            'product_type_id' => $productType->uuid,
            'is_active' => true,
            'barcodes' => ['NEW-456'],
            'presentations' => [[
                'presentation_id' => $presentation->uuid,
                'price' => 50,
                'min_stock' => 0,
            ]],
        ]);

        $product->refresh();
        $this->assertCount(1, $product->barcodes);
        $this->assertSame('NEW-456', $product->barcodes->first()->barcode);
    }

    public function test_returns_product_with_relations_loaded(): void
    {
        $product = Product::factory()->create();
        $productType = ProductType::factory()->create();
        $presentation = Presentation::factory()->create();

        $updated = $this->action()->execute($product, [
            'name' => 'Updated',
            'product_type_id' => $productType->uuid,
            'is_active' => true,
            'presentations' => [[
                'presentation_id' => $presentation->uuid,
                'price' => 100,
                'min_stock' => 0,
            ]],
        ]);

        $this->assertTrue($updated->relationLoaded('productType'));
        $this->assertTrue($updated->relationLoaded('barcodes'));
        $this->assertTrue($updated->relationLoaded('productPresentations'));
    }
}
