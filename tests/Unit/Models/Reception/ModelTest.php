<?php

namespace Tests\Unit\Models\Reception;

use App\Models\Model;
use App\Models\Reception;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Reception::tableName(), [
            'id', 'uuid', 'supplier_id', 'daily_cash_id', 'user_id',
            'supplier_invoice', 'total', 'notes', 'received_at',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Reception);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $reception = Reception::factory()->create();

        $this->assertNotNull($reception->uuid);
    }

    public function test_total_defaults_to_zero(): void
    {
        $reception = Reception::factory()->create(['total' => 0]);

        $this->assertEquals('0.00', $reception->total);
    }
}
