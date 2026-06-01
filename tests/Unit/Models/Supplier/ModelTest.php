<?php

namespace Tests\Unit\Models\Supplier;

use App\Models\Model;
use App\Models\Supplier;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Supplier::tableName(), [
            'id', 'uuid', 'name', 'contact_name', 'email', 'phone', 'address', 'notes',
            'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Supplier);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $supplier = Supplier::factory()->create();

        $this->assertNotNull($supplier->uuid);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $supplier = Supplier::factory()->create();

        $this->assertTrue($supplier->is_active);
    }

    public function test_inactive_state(): void
    {
        $supplier = Supplier::factory()->inactive()->create();

        $this->assertFalse($supplier->is_active);
    }
}
