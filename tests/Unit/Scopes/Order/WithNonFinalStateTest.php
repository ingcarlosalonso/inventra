<?php

namespace Tests\Unit\Scopes\Order;

use App\Models\Order;
use App\Models\Order\Scopes\WithNonFinalState;
use App\Models\OrderState;
use Tests\Unit\Models\ModelTestCase;

class WithNonFinalStateTest extends ModelTestCase
{
    public function test_includes_orders_with_non_final_state(): void
    {
        $nonFinal = OrderState::factory()->create(['is_final_state' => false]);
        $final = OrderState::factory()->create(['is_final_state' => true]);

        $included = Order::factory()->create(['order_state_id' => $nonFinal->id]);
        Order::factory()->create(['order_state_id' => $final->id]);

        $results = Order::query()->withScopes(new WithNonFinalState)->get();

        $this->assertTrue($results->contains($included));
        $this->assertCount(1, $results);
    }

    public function test_excludes_orders_with_final_state(): void
    {
        $final = OrderState::factory()->create(['is_final_state' => true]);
        Order::factory()->create(['order_state_id' => $final->id]);

        $results = Order::query()->withScopes(new WithNonFinalState)->get();

        $this->assertCount(0, $results);
    }
}
