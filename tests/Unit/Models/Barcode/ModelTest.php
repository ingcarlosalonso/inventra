<?php

namespace Tests\Unit\Models\Barcode;

use App\Models\Barcode;
use App\Models\Model;
use App\Models\ProductPresentation;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Barcode::tableName(), [
            'id', 'product_presentation_id', 'barcode', 'created_at', 'updated_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Barcode);
    }

    public function test_belongs_to_product_presentation(): void
    {
        $barcode = Barcode::factory()->create();

        $this->assertInstanceOf(ProductPresentation::class, $barcode->productPresentation);
    }
}
