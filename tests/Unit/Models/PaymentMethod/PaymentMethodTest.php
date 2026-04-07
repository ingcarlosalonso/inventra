<?php

namespace Tests\Unit\Models\PaymentMethod;

use App\Models\Model;
use App\Models\PaymentMethod;
use Tests\Unit\Models\ModelTestCase;

class PaymentMethodTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(PaymentMethod::tableName(), [
            'id', 'uuid', 'name', 'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new PaymentMethod());
    }
}
