<?php

namespace Tests\Unit\Scopes\ProductPresentation;

use App\Models\Barcode;
use App\Models\ProductPresentation;
use App\Models\ProductPresentation\Scopes\ByBarcode;
use Tests\Unit\Models\ModelTestCase;

class ByBarcodeTest extends ModelTestCase
{
    public function test_it_filters_by_barcode(): void
    {
        $pp = ProductPresentation::factory()->create();
        Barcode::factory()->create([
            'product_presentation_id' => $pp->id,
            'barcode' => '7791234567890',
        ]);

        $other = ProductPresentation::factory()->create();
        Barcode::factory()->create([
            'product_presentation_id' => $other->id,
            'barcode' => '9991234567890',
        ]);

        $results = ProductPresentation::withScopes(new ByBarcode('7791234567890'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals($pp->id, $results->first()->id);
    }

    public function test_it_returns_empty_when_barcode_not_found(): void
    {
        ProductPresentation::factory()->create();

        $results = ProductPresentation::withScopes(new ByBarcode('NONEXISTENT'))->get();

        $this->assertCount(0, $results);
    }
}
