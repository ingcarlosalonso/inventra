<?php

namespace App\Adapters;

use App\Exceptions\MercadoPagoException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MercadoPagoAdapter
{
    private const AUTH_URL = 'https://auth.mercadopago.com/authorization';

    private const API_URL = 'https://api.mercadopago.com';

    public function authorizationUrl(string $redirectUri, string $state, string $codeChallenge): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => (string) config('services.mercadopago.client_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'platform_id' => 'mp',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'scope' => 'offline_access',
        ]);

        return self::AUTH_URL.'?'.$query;
    }

    /**
     * @return array{access_token: string, refresh_token: string, user_id: int, public_key: string, live_mode: bool, expires_in: int, scope: string}
     */
    public function exchangeCodeForToken(string $code, string $redirectUri, string $codeVerifier): array
    {
        return $this->post('oauth/token', [
            'client_id' => (string) config('services.mercadopago.client_id'),
            'client_secret' => (string) config('services.mercadopago.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'code_verifier' => $codeVerifier,
        ], operation: 'oauth token exchange');
    }

    /**
     * @return array{access_token: string, refresh_token: string, user_id: int, public_key: string, live_mode: bool, expires_in: int, scope: string}
     */
    public function refreshToken(string $refreshToken): array
    {
        return $this->post('oauth/token', [
            'client_id' => (string) config('services.mercadopago.client_id'),
            'client_secret' => (string) config('services.mercadopago.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ], operation: 'oauth token refresh');
    }

    /**
     * @return array<int, array{id: string, pos_id: string, store_id: string, external_pos_id: ?string, operating_mode: string}>
     */
    public function listTerminals(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get(self::API_URL.'/terminals/v1/list');

        if ($response->failed()) {
            throw MercadoPagoException::requestFailed('list terminals', $response->status(), $response->body());
        }

        return $response->json('results', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function createOrder(string $accessToken, string $terminalId, string $externalReference, float $amount): array
    {
        $response = Http::withToken($accessToken)
            ->withHeaders(['X-Idempotency-Key' => Str::uuid()->toString()])
            ->post(self::API_URL.'/v1/orders', [
                'type' => 'point',
                'external_reference' => $externalReference,
                'transactions' => [
                    'payments' => [
                        ['amount' => number_format($amount, 2, '.', '')],
                    ],
                ],
                'config' => [
                    'point' => [
                        'terminal_id' => $terminalId,
                        'print_on_terminal' => 'no_ticket',
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw MercadoPagoException::requestFailed('create order', $response->status(), $response->body());
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $accessToken, string $mercadoPagoOrderId): array
    {
        $response = Http::withToken($accessToken)->get(self::API_URL."/v1/orders/{$mercadoPagoOrderId}");

        if ($response->failed()) {
            throw MercadoPagoException::requestFailed('get order', $response->status(), $response->body());
        }

        return $response->json();
    }

    public function cancelOrder(string $accessToken, string $mercadoPagoOrderId): void
    {
        $response = Http::withToken($accessToken)
            ->withHeaders(['X-Idempotency-Key' => Str::uuid()->toString()])
            ->post(self::API_URL."/v1/orders/{$mercadoPagoOrderId}/cancel");

        if ($response->failed()) {
            throw MercadoPagoException::requestFailed('cancel order', $response->status(), $response->body());
        }
    }

    /**
     * Revokes this app's authorization on Mercado Pago's side, so the connected account no
     * longer lists In-ventra as an authorized application once a tenant disconnects.
     */
    public function revokeAuthorization(string $accessToken, string $mercadoPagoUserId): void
    {
        $clientId = (string) config('services.mercadopago.client_id');

        $response = Http::withToken($accessToken)->delete(self::API_URL."/oauth/{$clientId}/{$mercadoPagoUserId}");

        if ($response->failed()) {
            throw MercadoPagoException::requestFailed('revoke authorization', $response->status(), $response->body());
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload, string $operation): array
    {
        $response = Http::acceptJson()->post(self::API_URL.'/'.$path, $payload);

        if ($response->failed()) {
            throw MercadoPagoException::requestFailed($operation, $response->status(), $response->body());
        }

        return $response->json();
    }
}
