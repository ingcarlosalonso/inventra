<?php

namespace Tests\Unit\Models\SaleItem;

use App\Models\Model;
use App\Models\SaleItem;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(SaleItem::tableName(), [
            'id', 'uuid', 'sale_id', 'product_presentation_id',
            'saleable_type', 'saleable_id',
            'description', 'quantity', 'unit_price',
            'discount_type', 'discount_value', 'discount_amount', 'total',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new SaleItem);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $item = SaleItem::factory()->create();

        $this->assertNotNull($item->uuid);
    }
}
