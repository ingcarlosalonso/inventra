<?php

namespace Tests\Unit\Models\ProductPresentation;

use App\Models\Barcode;
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

    public function test_has_many_barcodes(): void
    {
        $pp = ProductPresentation::factory()->create();
        Barcode::factory()->create(['product_presentation_id' => $pp->id]);

        $pp->refresh();
        $this->assertCount(1, $pp->barcodes);
        $this->assertInstanceOf(Barcode::class, $pp->barcodes->first());
    }
}
