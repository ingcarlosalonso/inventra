<?php

namespace Tests\Unit\Models\MercadoPagoCredential;

use App\Models\MercadoPagoCredential;
use App\Models\PaymentMethod;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_payment_method_nullable(): void
    {
        $credential = MercadoPagoCredential::factory()->create(['payment_method_id' => null]);

        $this->assertNull($credential->paymentMethod);
    }

    public function test_belongs_to_payment_method(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $credential = MercadoPagoCredential::factory()->create(['payment_method_id' => $paymentMethod->id]);

        $this->assertInstanceOf(PaymentMethod::class, $credential->paymentMethod);
    }
}
