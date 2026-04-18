<?php

namespace Tests\Unit\Models\DailyCash;

use App\Models\DailyCash;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_it_has_payments_relation(): void
    {
        $dailyCash = DailyCash::factory()->create();
        $pm = PaymentMethod::factory()->create();

        Payment::factory()->count(2)->create([
            'daily_cash_id' => $dailyCash->id,
            'payable_type' => 'sale',
            'payment_method_id' => $pm->id,
        ]);

        $this->assertCount(2, $dailyCash->payments);
        $this->assertInstanceOf(Payment::class, $dailyCash->payments->first());
    }
}
