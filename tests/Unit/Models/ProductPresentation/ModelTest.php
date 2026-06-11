<?php

namespace Tests\Unit\Models\ProductPresentation;

use App\Models\Model;
use App\Models\ProductPresentation;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(ProductPresentation::tableName(), [
            'id', 'uuid', 'product_id', 'presentation_id',
            'price', 'stock', 'min_stock',
            'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new ProductPresentation);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $pp = ProductPresentation::factory()->create();

        $this->assertNotNull($pp->uuid);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $pp = ProductPresentation::factory()->create();

        $this->assertTrue($pp->is_active);
    }

    public function test_inactive_state(): void
    {
        $pp = ProductPresentation::factory()->inactive()->create();

        $this->assertFalse($pp->is_active);
    }
}
