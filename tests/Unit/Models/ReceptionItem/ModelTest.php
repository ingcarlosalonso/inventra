<?php

namespace Tests\Unit\Models\ReceptionItem;

use App\Models\Model;
use App\Models\ReceptionItem;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(ReceptionItem::tableName(), [
            'id', 'uuid', 'reception_id', 'product_presentation_id',
            'quantity', 'unit_cost', 'total',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new ReceptionItem);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $item = ReceptionItem::factory()->create();

        $this->assertNotNull($item->uuid);
    }
}
