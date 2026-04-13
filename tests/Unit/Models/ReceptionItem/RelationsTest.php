<?php

namespace Tests\Unit\Models\ReceptionItem;

use App\Models\ProductPresentation;
use App\Models\Reception;
use App\Models\ReceptionItem;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_reception(): void
    {
        $item = ReceptionItem::factory()->create();

        $this->assertInstanceOf(Reception::class, $item->reception);
    }

    public function test_belongs_to_product_presentation(): void
    {
        $item = ReceptionItem::factory()->create();

        $this->assertInstanceOf(ProductPresentation::class, $item->productPresentation);
    }
}
