<?php

namespace Tests\Unit\Models\Order;

use App\Models\Model;
use App\Models\Order;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Order::tableName(), [
            'id', 'uuid', 'sale_id', 'client_id', 'courier_id', 'order_state_id',
            'user_id', 'point_of_sale_id', 'currency_id',
            'address', 'notes', 'requires_delivery', 'delivery_date', 'scheduled_at',
            'subtotal', 'discount_type', 'discount_value', 'discount_amount', 'total',
            'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Order);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $order = Order::factory()->create();

        $this->assertNotNull($order->uuid);
    }

    public function test_total_defaults_to_zero(): void
    {
        $order = Order::factory()->create(['total' => 0]);

        $this->assertEquals('0.00', $order->total);
    }

    public function test_requires_delivery_cast_to_boolean(): void
    {
        $order = Order::factory()->create(['requires_delivery' => true]);

        $this->assertTrue($order->requires_delivery);
    }
}
