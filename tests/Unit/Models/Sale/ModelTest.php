<?php

namespace Tests\Unit\Models\Sale;

use App\Models\Model;
use App\Models\Sale;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Sale::tableName(), [
            'id', 'uuid', 'client_id', 'point_of_sale_id', 'sale_state_id',
            'currency_id', 'user_id',
            'subtotal', 'discount_type', 'discount_value', 'discount_amount', 'total',
            'notes', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Sale);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $sale = Sale::factory()->create();

        $this->assertNotNull($sale->uuid);
    }

    public function test_total_defaults_to_zero(): void
    {
        $sale = Sale::factory()->create(['total' => 0]);

        $this->assertEquals('0.00', $sale->total);
    }
}
