<?php

namespace Tests\Unit\Models\CompositeProduct;

use App\Models\CompositeProduct;
use App\Models\Model;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(CompositeProduct::tableName(), [
            'id', 'uuid', 'name', 'code', 'is_active',
            'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new CompositeProduct);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();

        $this->assertNotNull($compositeProduct->uuid);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $compositeProduct = CompositeProduct::factory()->create();

        $this->assertTrue($compositeProduct->is_active);
    }

    public function test_inactive_state(): void
    {
        $compositeProduct = CompositeProduct::factory()->inactive()->create();

        $this->assertFalse($compositeProduct->is_active);
    }
}
