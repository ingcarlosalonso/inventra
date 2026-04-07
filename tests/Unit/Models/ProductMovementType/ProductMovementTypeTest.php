<?php

namespace Tests\Unit\Models\ProductMovementType;

use App\Models\Model;
use App\Models\ProductMovementType;
use Tests\Unit\Models\ModelTestCase;

class ProductMovementTypeTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(ProductMovementType::tableName(), [
            'id', 'uuid', 'name', 'is_income', 'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new ProductMovementType());
    }
}
