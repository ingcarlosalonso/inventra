<?php

namespace Database\Factories;

use App\Enums\MercadoPagoOrderStatus;
use App\Models\MercadoPagoOrder;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MercadoPagoOrder>
 */
class MercadoPagoOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payable_type' => 'sale',
            'payable_id' => Sale::factory(),
            'point_of_sale_id' => PointOfSale::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'mercado_pago_order_id' => (string) fake()->unique()->randomNumber(9, true),
            'external_reference' => fake()->uuid(),
            'status' => MercadoPagoOrderStatus::Created,
            'amount' => fake()->randomFloat(2, 1, 10000),
            'mercado_pago_payment_id' => null,
            'failure_detail' => null,
            'processed_at' => null,
        ];
    }
}
