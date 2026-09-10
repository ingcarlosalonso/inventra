<?php

namespace Tests\Unit\Models\MercadoPagoOrder;

use App\Models\MercadoPagoOrder;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Sale;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_morph_to_payable(): void
    {
        $sale = Sale::factory()->create();
        $order = MercadoPagoOrder::factory()->create(['payable_type' => 'sale', 'payable_id' => $sale->id]);

        $this->assertInstanceOf(Sale::class, $order->payable);
    }

    public function test_belongs_to_point_of_sale(): void
    {
        $pos = PointOfSale::factory()->create();
        $order = MercadoPagoOrder::factory()->create(['point_of_sale_id' => $pos->id]);

        $this->assertInstanceOf(PointOfSale::class, $order->pointOfSale);
    }

    public function test_belongs_to_payment_method(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $order = MercadoPagoOrder::factory()->create(['payment_method_id' => $paymentMethod->id]);

        $this->assertInstanceOf(PaymentMethod::class, $order->paymentMethod);
    }
}
