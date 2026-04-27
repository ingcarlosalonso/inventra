<?php

namespace Tests\Unit\Models\SaleState;

use App\Models\Model;
use App\Models\SaleState;
use Tests\Unit\Models\ModelTestCase;

class SaleStateTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(SaleState::tableName(), [
            'id',
            'uuid',
            'name',
            'color',
            'is_default',
            'is_final_state',
            'is_active',
            'sort_order',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new SaleState());
    }
}
