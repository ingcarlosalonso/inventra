<?php

namespace Tests\Unit\Models\Promotion;

use App\Models\Model;
use App\Models\Promotion;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Promotion::tableName(), [
            'id', 'uuid', 'name', 'code', 'sale_price', 'is_active',
            'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Promotion);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $promotion = Promotion::factory()->create();

        $this->assertNotNull($promotion->uuid);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $promotion = Promotion::factory()->create();

        $this->assertTrue($promotion->is_active);
    }

    public function test_inactive_state(): void
    {
        $promotion = Promotion::factory()->inactive()->create();

        $this->assertFalse($promotion->is_active);
    }
}
