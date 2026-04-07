<?php

namespace Tests\Unit\Models\CashMovementType;

use App\Models\CashMovementType;
use App\Models\Model;
use Tests\Unit\Models\ModelTestCase;

class CashMovementTypeTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(CashMovementType::tableName(), [
            'id', 'uuid', 'name', 'is_income', 'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new CashMovementType());
    }
}
