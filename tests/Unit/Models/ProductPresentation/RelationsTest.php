<?php

namespace Tests\Unit\Models\ProductPresentation;

use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductPresentation;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_product(): void
    {
        $pp = ProductPresentation::factory()->create();

        $this->assertInstanceOf(Product::class, $pp->product);
    }

    public function test_belongs_to_presentation(): void
    {
        $pp = ProductPresentation::factory()->create();

        $this->assertInstanceOf(Presentation::class, $pp->presentation);
    }
}
