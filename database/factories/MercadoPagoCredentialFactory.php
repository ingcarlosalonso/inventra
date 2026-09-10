<?php

namespace Database\Factories;

use App\Models\MercadoPagoCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MercadoPagoCredential>
 */
class MercadoPagoCredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'access_token' => 'APP_USR-'.fake()->uuid(),
            'refresh_token' => 'TG-'.fake()->uuid(),
            'mercado_pago_user_id' => (string) fake()->randomNumber(9, true),
            'public_key' => 'APP_USR-'.fake()->uuid(),
            'scope' => 'offline_access read write',
            'live_mode' => true,
            'token_expires_at' => now()->addDays(180),
            'connected_at' => now(),
            'payment_method_id' => null,
        ];
    }
}
