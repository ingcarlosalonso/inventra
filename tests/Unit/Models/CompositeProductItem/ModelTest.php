<?php

namespace Tests\Unit\Models\CompositeProductItem;

use App\Models\CompositeProduct;
use App\Models\CompositeProductItem;
use App\Models\Model;
use App\Models\Product;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(CompositeProductItem::tableName(), [
            'id', 'composite_product_id', 'product_id', 'quantity',
            'created_by', 'updated_by', 'created_at', 'updated_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new CompositeProductItem);
    }

    public function test_belongs_to_composite_product(): void
    {
        $item = CompositeProductItem::factory()->create();

        $this->assertInstanceOf(CompositeProduct::class, $item->compositeProduct);
    }

    public function test_belongs_to_product(): void
    {
        $item = CompositeProductItem::factory()->create();

        $this->assertInstanceOf(Product::class, $item->product);
    }
}
