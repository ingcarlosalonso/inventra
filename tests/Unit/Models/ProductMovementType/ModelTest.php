<?php

namespace Tests\Unit\Models\ProductMovementType;

use App\Models\Model;
use App\Models\ProductMovementType;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns('product_movement_types', [
            'id', 'uuid', 'name', 'is_income', 'is_active',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new ProductMovementType);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $type = ProductMovementType::factory()->create();

        $this->assertNotNull($type->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $type->uuid
        );
    }

    public function test_is_active_defaults_to_true(): void
    {
        $type = ProductMovementType::factory()->create();

        $this->assertTrue($type->is_active);
    }

    public function test_is_income_defaults_to_true(): void
    {
        $type = ProductMovementType::factory()->create();

        $this->assertTrue($type->is_income);
    }

    public function test_outgoing_state(): void
    {
        $type = ProductMovementType::factory()->outgoing()->create();

        $this->assertFalse($type->is_income);
    }
}
