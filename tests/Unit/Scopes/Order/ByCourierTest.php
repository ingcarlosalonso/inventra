<?php

namespace Tests\Unit\Scopes\Order;

use App\Models\Courier;
use App\Models\Order;
use App\Models\Order\Scopes\ByCourier;
use Tests\Unit\Models\ModelTestCase;

class ByCourierTest extends ModelTestCase
{
    public function test_filters_by_courier_id(): void
    {
        $courier = Courier::factory()->create();
        $other = Courier::factory()->create();

        $match = Order::factory()->create(['courier_id' => $courier->id]);
        Order::factory()->create(['courier_id' => $other->id]);

        $results = Order::query()->withScopes(new ByCourier($courier->id))->get();

        $this->assertCount(1, $results);
        $this->assertSame($match->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Order::factory()->create();

        $results = Order::query()->withScopes(new ByCourier(99999))->get();

        $this->assertCount(0, $results);
    }
}
