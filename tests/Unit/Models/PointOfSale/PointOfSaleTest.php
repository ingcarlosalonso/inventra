<?php

namespace Tests\Unit\Models\PointOfSale;

use App\Models\Model;
use App\Models\PointOfSale;
use Tests\Unit\Models\ModelTestCase;

class PointOfSaleTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(PointOfSale::tableName(), [
            'id', 'uuid', 'number', 'name', 'address', 'is_active',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new PointOfSale());
    }
}
