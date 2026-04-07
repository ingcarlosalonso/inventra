<?php

namespace Tests\Unit\Models\ProductType;

use App\Models\Model;
use App\Models\ProductType;
use Tests\Unit\Models\ModelTestCase;

class ProductTypeTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(ProductType::tableName(), [
            'id', 'uuid', 'name', 'parent_id', 'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new ProductType());
    }
}
