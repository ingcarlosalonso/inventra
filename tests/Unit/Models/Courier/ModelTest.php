<?php

namespace Tests\Unit\Models\Courier;

use App\Models\Courier;
use App\Models\Model;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Courier::tableName(), [
            'id', 'uuid', 'name', 'email', 'phone', 'is_active',
            'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Courier);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $courier = Courier::factory()->create();

        $this->assertNotNull($courier->uuid);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $courier = Courier::factory()->create();

        $this->assertTrue($courier->is_active);
    }
}
