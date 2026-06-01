<?php

namespace Tests\Unit\Models\CompositeProduct;

use App\Models\CompositeProduct;
use App\Models\CompositeProductItem;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_has_many_items(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();
        CompositeProductItem::factory()->count(2)->create(['composite_product_id' => $compositeProduct->id]);

        $this->assertCount(2, $compositeProduct->items);
    }

    public function test_items_collection_is_empty_by_default(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();

        $this->assertCount(0, $compositeProduct->items);
    }
}
