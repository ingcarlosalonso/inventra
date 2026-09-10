<?php

namespace Tests\Unit\Scopes;

use App\Models\MercadoPagoOrder;
use App\Models\MercadoPagoOrder\Scopes\ByMercadoPagoOrderId;
use Tests\Unit\Models\ModelTestCase;

class ByMercadoPagoOrderIdTest extends ModelTestCase
{
    public function test_filters_by_mercado_pago_order_id(): void
    {
        $order = MercadoPagoOrder::factory()->create(['mercado_pago_order_id' => 'ORDER1']);
        MercadoPagoOrder::factory()->create(['mercado_pago_order_id' => 'ORDER2']);

        $results = MercadoPagoOrder::query()->withScopes(new ByMercadoPagoOrderId('ORDER1'))->get();

        $this->assertCount(1, $results);
        $this->assertSame($order->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        MercadoPagoOrder::factory()->create(['mercado_pago_order_id' => 'ORDER3']);

        $results = MercadoPagoOrder::query()->withScopes(new ByMercadoPagoOrderId('does-not-exist'))->get();

        $this->assertCount(0, $results);
    }
}
