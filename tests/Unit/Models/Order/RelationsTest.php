<?php

namespace Tests\Unit\Models\Order;

use App\Models\Courier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderState;
use App\Models\Payment;
use App\Models\PointOfSale;
use App\Models\User;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_client_nullable(): void
    {
        $order = Order::factory()->create(['client_id' => null]);

        $this->assertNull($order->client);
    }

    public function test_belongs_to_courier_nullable(): void
    {
        $order = Order::factory()->create(['courier_id' => null]);

        $this->assertNull($order->courier);
    }

    public function test_belongs_to_courier(): void
    {
        $courier = Courier::factory()->create();
        $order = Order::factory()->create(['courier_id' => $courier->id]);

        $this->assertInstanceOf(Courier::class, $order->courier);
    }

    public function test_belongs_to_order_state(): void
    {
        $state = OrderState::factory()->create();
        $order = Order::factory()->create(['order_state_id' => $state->id]);

        $this->assertInstanceOf(OrderState::class, $order->orderState);
    }

    public function test_belongs_to_point_of_sale_nullable(): void
    {
        $order = Order::factory()->create(['point_of_sale_id' => null]);

        $this->assertNull($order->pointOfSale);
    }

    public function test_belongs_to_point_of_sale(): void
    {
        $pos = PointOfSale::factory()->create();
        $order = Order::factory()->create(['point_of_sale_id' => $pos->id]);

        $this->assertInstanceOf(PointOfSale::class, $order->pointOfSale);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
    }

    public function test_has_many_items(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $this->assertCount(2, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items->first());
    }

    public function test_morph_many_payments(): void
    {
        $order = Order::factory()->create();
        Payment::factory()->count(2)->create(['payable_type' => 'order', 'payable_id' => $order->id]);

        $this->assertCount(2, $order->payments);
        $this->assertInstanceOf(Payment::class, $order->payments->first());
    }
}
