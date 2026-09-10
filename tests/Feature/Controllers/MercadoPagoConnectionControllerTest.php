<?php

namespace Tests\Feature\Controllers;

use App\Models\MercadoPagoCredential;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;
use Tests\Feature\TenantFeatureTestCase;

class MercadoPagoConnectionControllerTest extends TenantFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mercadopago.client_id' => 'test-client-id',
            'services.mercadopago.client_secret' => 'test-client-secret',
        ]);
    }

    public function test_show_returns_not_connected_when_no_credential(): void
    {
        $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->getJson('/api/v1/settings/mercado-pago')
            ->assertOk()
            ->assertJsonPath('data.connected', false);
    }

    public function test_show_returns_connected_credential(): void
    {
        MercadoPagoCredential::factory()->create();

        $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->getJson('/api/v1/settings/mercado-pago')
            ->assertOk()
            ->assertJsonPath('data.connected', true);
    }

    public function test_show_requires_permission(): void
    {
        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->getJson('/api/v1/settings/mercado-pago')
            ->assertForbidden();
    }

    public function test_show_requires_auth(): void
    {
        $this->getJson('/api/v1/settings/mercado-pago')->assertUnauthorized();
    }

    public function test_connect_returns_authorization_url(): void
    {
        $response = $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->getJson('/api/v1/settings/mercado-pago/connect')
            ->assertOk()
            ->assertJsonStructure(['url']);

        $this->assertStringStartsWith('https://auth.mercadopago.com/authorization?', $response->json('url'));

        parse_str(parse_url($response->json('url'), PHP_URL_QUERY), $query);
        $this->assertSame(
            'http://development.central.in-ventra.com/oauth/mercadopago/callback',
            $query['redirect_uri']
        );
    }

    public function test_update_sets_payment_method(): void
    {
        MercadoPagoCredential::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->patchJson('/api/v1/settings/mercado-pago', ['payment_method_id' => $paymentMethod->uuid])
            ->assertOk();

        $this->assertDatabaseHas('mercado_pago_credentials', ['payment_method_id' => $paymentMethod->id], 'tenant');
    }

    public function test_destroy_deletes_credential(): void
    {
        Http::fake(['https://api.mercadopago.com/oauth/*' => Http::response([], 200)]);

        $credential = MercadoPagoCredential::factory()->create([
            'access_token' => 'APP_USR-token',
            'mercado_pago_user_id' => '123456789',
        ]);

        $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->deleteJson('/api/v1/settings/mercado-pago')
            ->assertNoContent();

        $this->assertDatabaseCount('mercado_pago_credentials', 0, 'tenant');

        Http::assertSent(function ($request) use ($credential) {
            return $request->url() === "https://api.mercadopago.com/oauth/test-client-id/{$credential->mercado_pago_user_id}"
                && $request->method() === 'DELETE'
                && $request->hasHeader('Authorization', 'Bearer APP_USR-token');
        });
    }

    public function test_destroy_still_deletes_credential_when_revocation_fails(): void
    {
        Http::fake(['https://api.mercadopago.com/oauth/*' => Http::response(['message' => 'invalid_token'], 401)]);

        MercadoPagoCredential::factory()->create();

        $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->deleteJson('/api/v1/settings/mercado-pago')
            ->assertNoContent();

        $this->assertDatabaseCount('mercado_pago_credentials', 0, 'tenant');
    }

    public function test_destroy_is_a_noop_when_no_credential_exists(): void
    {
        $this->actingAs($this->userWithPermissions('manage_mercadopago'), 'sanctum')
            ->deleteJson('/api/v1/settings/mercado-pago')
            ->assertNoContent();

        Http::assertNothingSent();
    }
}
