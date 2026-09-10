<?php

namespace Tests\Feature\Controllers;

use App\Models\MercadoPagoCredential;
use Illuminate\Support\Facades\Http;
use Tests\Feature\TenantFeatureTestCase;

class MercadoPagoTerminalControllerTest extends TenantFeatureTestCase
{
    public function test_index_returns_terminals_from_mercado_pago(): void
    {
        MercadoPagoCredential::factory()->create(['access_token' => 'APP_USR-token']);

        Http::fake([
            'https://api.mercadopago.com/terminals/v1/list' => Http::response([
                'results' => [
                    ['id' => 'TERM1', 'pos_id' => 'POS1', 'store_id' => 'STORE1', 'external_pos_id' => null, 'operating_mode' => 'PDV'],
                ],
            ]),
        ]);

        $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->getJson('/api/v1/settings/mercado-pago/terminals')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'TERM1');
    }

    public function test_index_fails_when_not_connected(): void
    {
        $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->getJson('/api/v1/settings/mercado-pago/terminals')
            ->assertUnprocessable();
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->getJson('/api/v1/settings/mercado-pago/terminals')
            ->assertForbidden();
    }
}
