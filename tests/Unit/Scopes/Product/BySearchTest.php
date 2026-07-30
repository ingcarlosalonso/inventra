<?php

namespace Tests\Unit\Scopes\Product;

use App\Models\Barcode;
use App\Models\Product;
use App\Models\Product\Scopes\BySearch;
use App\Models\ProductPresentation;
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
        $pp = ProductPresentation::factory()->create(['product_id' => $product->id]);
        Barcode::factory()->create(['product_presentation_id' => $pp->id, 'barcode' => '1234567890123']);
        Product::factory()->create(['name' => 'Sin código']);

        $results = Product::query()->withScopes(new BySearch('1234567890123'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals($product->id, $results->first()->id);
    }

    public function test_matches_multi_word_search_regardless_of_extra_words(): void
    {
        Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        Product::factory()->create(['name' => 'Sprite 500ml']);

        $results = Product::query()->withScopes(new BySearch('coca cola 500'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Coca-Cola 500ml', $results->first()->name);
    }

    public function test_matches_multi_word_search_regardless_of_word_order(): void
    {
        Product::factory()->create(['name' => 'Coca-Cola 500ml']);

        $results = Product::query()->withScopes(new BySearch('500ml cola'))->get();

        $this->assertCount(1, $results);
    }

    public function test_requires_all_words_to_match(): void
    {
        Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        Product::factory()->create(['name' => 'Coca-Cola 1L']);

        $results = Product::query()->withScopes(new BySearch('cola 500'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Coca-Cola 500ml', $results->first()->name);
    }

    public function test_blank_search_does_not_filter(): void
    {
        $products = Product::factory()->count(2)->create();

        $results = Product::query()->withScopes(new BySearch('   '))->get();

        $this->assertTrue($products->every(fn ($p) => $results->contains('id', $p->id)));
    }
}
