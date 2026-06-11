<?php

namespace Tests\Unit\Models\OrderState;

use App\Models\Model;
use App\Models\OrderState;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(OrderState::tableName(), [
            'id', 'uuid', 'name', 'color', 'is_default', 'is_final_state', 'is_active',
            'sort_order', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new OrderState);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $state = OrderState::factory()->create();

        $this->assertNotNull($state->uuid);
    }

    public function test_boolean_casts_work(): void
    {
        $state = OrderState::factory()->create(['is_default' => true, 'is_active' => false]);

        $this->assertTrue($state->is_default);
        $this->assertFalse($state->is_active);
    }
}
