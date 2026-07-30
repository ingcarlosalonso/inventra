<?php

namespace Tests\Unit\Scopes\ProductPresentation;

use App\Models\Barcode;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\ProductPresentation\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_product_name(): void
    {
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        ProductPresentation::factory()->create(['product_id' => $product->id]);

        $other = Product::factory()->create(['name' => 'Sprite 500ml']);
        ProductPresentation::factory()->create(['product_id' => $other->id]);

        $results = ProductPresentation::withScopes(new BySearch('coca cola 500'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals($product->id, $results->first()->product_id);
    }

    public function test_filters_by_product_barcode(): void
    {
        $product = Product::factory()->create();
        $pp = ProductPresentation::factory()->create(['product_id' => $product->id]);
        Barcode::factory()->create(['product_presentation_id' => $pp->id, 'barcode' => '1234567890123']);

        $other = Product::factory()->create();
        ProductPresentation::factory()->create(['product_id' => $other->id]);

        $results = ProductPresentation::withScopes(new BySearch('1234567890123'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals($pp->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        ProductPresentation::factory()->create();

        $results = ProductPresentation::withScopes(new BySearch('nonexistent_xyz'))->get();

        $this->assertCount(0, $results);
    }
}
