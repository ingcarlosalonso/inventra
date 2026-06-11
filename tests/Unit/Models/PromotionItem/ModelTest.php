<?php

namespace Tests\Unit\Models\PromotionItem;

use App\Models\Model;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionItem;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(PromotionItem::tableName(), [
            'id', 'promotion_id', 'product_id', 'quantity',
            'created_by', 'updated_by', 'created_at', 'updated_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new PromotionItem);
    }

    public function test_belongs_to_promotion(): void
    {
        $item = PromotionItem::factory()->create();

        $this->assertInstanceOf(Promotion::class, $item->promotion);
    }

    public function test_belongs_to_product(): void
    {
        $item = PromotionItem::factory()->create();

        $this->assertInstanceOf(Product::class, $item->product);
    }
}
