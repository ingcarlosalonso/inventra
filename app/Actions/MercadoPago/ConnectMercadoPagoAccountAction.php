<?php

namespace App\Actions\MercadoPago;

use App\Models\MercadoPagoCredential;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class ConnectMercadoPagoAccountAction
{
    /**
     * @param  array{access_token: string, refresh_token: string, user_id: int|string, public_key?: string, live_mode?: bool, expires_in?: int, scope?: string}  $tokenResponse
     */
    public function execute(array $tokenResponse): MercadoPagoCredential
    {
        $credential = MercadoPagoCredential::query()->first() ?? new MercadoPagoCredential;

        $credential->fill([
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'],
            'mercado_pago_user_id' => (string) $tokenResponse['user_id'],
            'public_key' => $tokenResponse['public_key'] ?? null,
            'scope' => $tokenResponse['scope'] ?? null,
            'live_mode' => $tokenResponse['live_mode'] ?? true,
            'token_expires_at' => isset($tokenResponse['expires_in'])
                ? Carbon::now()->addSeconds((int) $tokenResponse['expires_in'])
                : null,
            'connected_at' => Carbon::now(),
        ])->save();

        Tenant::current()?->update(['mercado_pago_user_id' => (string) $tokenResponse['user_id']]);

        return $credential;
    }
}
