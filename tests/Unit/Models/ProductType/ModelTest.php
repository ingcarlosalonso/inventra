<?php

namespace Tests\Unit\Models\ProductType;

use App\Models\Model;
use App\Models\ProductType;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(ProductType::tableName(), [
            'id', 'uuid', 'name', 'is_active', 'parent_id',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new ProductType);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $type = ProductType::factory()->create();

        $this->assertNotNull($type->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $type->uuid
        );
    }

    public function test_is_active_defaults_to_true(): void
    {
        $type = ProductType::factory()->create();

        $this->assertTrue($type->is_active);
    }
}
