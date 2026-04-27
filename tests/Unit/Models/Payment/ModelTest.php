<?php

namespace Tests\Unit\Models\Payment;

use App\Models\Model;
use App\Models\Payment;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Payment::tableName(), [
            'id', 'uuid', 'payable_type', 'payable_id',
            'payment_method_id', 'currency_id', 'daily_cash_id',
            'amount', 'exchange_rate', 'notes',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Payment);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $payment = Payment::factory()->create();

        $this->assertNotNull($payment->uuid);
    }
}
