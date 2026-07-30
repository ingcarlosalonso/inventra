<?php

namespace Tests\Unit\Models\Brand;

use App\Models\Brand;
use App\Models\Model;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Brand::tableName(), [
            'id', 'uuid', 'name', 'is_active',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Brand);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $brand = Brand::factory()->create();

        $this->assertNotNull($brand->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $brand->uuid
        );
    }

    public function test_is_active_defaults_to_true(): void
    {
        $brand = Brand::factory()->create();

        $this->assertTrue($brand->is_active);
    }
}
