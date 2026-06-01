<?php

namespace Tests\Unit\Scopes\Product;

use App\Models\Barcode;
use App\Models\Product;
use App\Models\Product\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        Product::factory()->create(['name' => 'Rosa Roja']);
        Product::factory()->create(['name' => 'Tulipán Blanco']);

        $results = Product::query()->withScopes(new BySearch('Rosa'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Rosa Roja', $results->first()->name);
    }

    public function test_filters_by_description(): void
    {
        Product::factory()->create(['name' => 'Producto A', 'description' => 'Descripción especial']);
        Product::factory()->create(['name' => 'Producto B', 'description' => 'Sin coincidencia']);

        $results = Product::query()->withScopes(new BySearch('especial'))->get();

        $this->assertCount(1, $results);
    }

    public function test_filters_by_barcode(): void
    {
        $product = Product::factory()->create(['name' => 'Producto con código']);
        Barcode::factory()->create(['product_id' => $product->id, 'barcode' => '1234567890123']);
        Product::factory()->create(['name' => 'Sin código']);

        $results = Product::query()->withScopes(new BySearch('1234567890123'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals($product->id, $results->first()->id);
    }
}
