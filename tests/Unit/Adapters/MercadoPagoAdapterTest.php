<?php

namespace Tests\Unit\Adapters;

use App\Adapters\MercadoPagoAdapter;
use App\Exceptions\MercadoPagoException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mercadopago.client_id' => 'test-client-id',
            'services.mercadopago.client_secret' => 'test-client-secret',
        ]);
    }

    public function test_authorization_url_includes_pkce_and_client_params(): void
    {
        $url = (new MercadoPagoAdapter)->authorizationUrl('https://acme.in-ventra.com/callback', 'state123', 'challenge123');

        $this->assertStringStartsWith('https://auth.mercadopago.com/authorization?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('test-client-id', $query['client_id']);
        $this->assertSame('https://acme.in-ventra.com/callback', $query['redirect_uri']);
        $this->assertSame('state123', $query['state']);
        $this->assertSame('challenge123', $query['code_challenge']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('offline_access', $query['scope']);
    }

    public function test_exchange_code_for_token_returns_decoded_response(): void
    {
        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response([
                'access_token' => 'APP_USR-token',
                'refresh_token' => 'TG-token',
                'user_id' => 123456789,
                'public_key' => 'APP_USR-public',
                'live_mode' => true,
                'expires_in' => 15552000,
                'scope' => 'offline_access',
            ]),
        ]);

        $result = (new MercadoPagoAdapter)->exchangeCodeForToken('code123', 'https://acme.in-ventra.com/callback', 'verifier123');

        $this->assertSame('APP_USR-token', $result['access_token']);
        Http::assertSent(fn ($request) => $request['grant_type'] === 'authorization_code' && $request['code'] === 'code123');
    }

    public function test_exchange_code_for_token_throws_on_failure(): void
    {
        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response(['message' => 'invalid_grant'], 400),
        ]);

        $this->expectException(MercadoPagoException::class);

        (new MercadoPagoAdapter)->exchangeCodeForToken('bad-code', 'https://acme.in-ventra.com/callback', 'verifier123');
    }

    public function test_list_terminals_returns_results_array(): void
    {
        Http::fake([
            'https://api.mercadopago.com/terminals/v1/list' => Http::response([
                'results' => [
                    ['id' => 'TERM123', 'pos_id' => 'POS1', 'store_id' => 'STORE1', 'external_pos_id' => null, 'operating_mode' => 'PDV'],
                ],
            ]),
        ]);

        $terminals = (new MercadoPagoAdapter)->listTerminals('APP_USR-token');

        $this->assertCount(1, $terminals);
        $this->assertSame('TERM123', $terminals[0]['id']);
    }

    public function test_create_order_sends_amount_as_formatted_string_and_idempotency_key(): void
    {
        Http::fake([
            'https://api.mercadopago.com/v1/orders' => Http::response(['id' => 'ORDER1', 'status' => 'created']),
        ]);

        $result = (new MercadoPagoAdapter)->createOrder('APP_USR-token', 'TERM123', 'ext-ref-1', 150.5);

        $this->assertSame('ORDER1', $result['id']);
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Idempotency-Key')
                && $request['config']['point']['terminal_id'] === 'TERM123'
                && $request['transactions']['payments'][0]['amount'] === '150.50';
        });
    }

    public function test_get_order_throws_on_failure(): void
    {
        Http::fake([
            'https://api.mercadopago.com/v1/orders/ORDER1' => Http::response(['message' => 'not_found'], 404),
        ]);

        $this->expectException(MercadoPagoException::class);

        (new MercadoPagoAdapter)->getOrder('APP_USR-token', 'ORDER1');
    }

    public function test_revoke_authorization_sends_delete_with_client_id_and_user_id(): void
    {
        Http::fake(['https://api.mercadopago.com/oauth/*' => Http::response([], 200)]);

        (new MercadoPagoAdapter)->revokeAuthorization('APP_USR-token', '123456789');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mercadopago.com/oauth/test-client-id/123456789'
                && $request->method() === 'DELETE'
                && $request->hasHeader('Authorization', 'Bearer APP_USR-token');
        });
    }

    public function test_revoke_authorization_throws_on_failure(): void
    {
        Http::fake(['https://api.mercadopago.com/oauth/*' => Http::response(['message' => 'invalid_token'], 401)]);

        $this->expectException(MercadoPagoException::class);

        (new MercadoPagoAdapter)->revokeAuthorization('APP_USR-token', '123456789');
    }
}
