<?php

namespace Tests\Unit\Models\Promotion;

use App\Models\Promotion;
use App\Models\PromotionItem;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_has_many_items(): void
    {
        $promotion = Promotion::factory()->create();
        PromotionItem::factory()->count(3)->create(['promotion_id' => $promotion->id]);

        $this->assertCount(3, $promotion->items);
    }

    public function test_items_collection_is_empty_by_default(): void
    {
        $promotion = Promotion::factory()->create();

        $this->assertCount(0, $promotion->items);
    }
}
