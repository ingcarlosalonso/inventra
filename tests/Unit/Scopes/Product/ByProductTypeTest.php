<?php

namespace Tests\Unit\Scopes\Product;

use App\Models\Product;
use App\Models\Product\Scopes\ByProductType;
use App\Models\ProductType;
use Tests\Unit\Models\ModelTestCase;

class ByProductTypeTest extends ModelTestCase
{
    public function test_filters_by_product_type(): void
    {
        $type = ProductType::factory()->create();
        $other = ProductType::factory()->create();

        $product = Product::factory()->create(['product_type_id' => $type->id]);
        Product::factory()->create(['product_type_id' => $other->id]);

        $results = Product::query()->withScopes(new ByProductType($type->id))->get();

        $this->assertCount(1, $results);
        $this->assertSame($product->id, $results->first()->id);
    }

    public function test_returns_empty_for_unknown_type(): void
    {
        Product::factory()->create();

        $results = Product::query()->withScopes(new ByProductType(99999))->get();

        $this->assertCount(0, $results);
    }
}
