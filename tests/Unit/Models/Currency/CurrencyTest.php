<?php

namespace Tests\Unit\Models\Currency;

use App\Models\Currency;
use App\Models\Model;
use Tests\Unit\Models\ModelTestCase;

class CurrencyTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Currency::tableName(), [
            'id', 'uuid', 'name', 'symbol', 'iso_code', 'is_default', 'is_active',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Currency());
    }
}
