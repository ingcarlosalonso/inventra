<?php

namespace App\Http\Controllers\Central;

use App\Actions\MercadoPago\ConnectMercadoPagoAccountAction;
use App\Adapters\MercadoPagoAdapter;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MercadoPagoConnectionController;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class MercadoPagoOAuthCallbackController extends Controller
{
    public function __construct(private MercadoPagoAdapter $adapter) {}

    public function handle(Request $request): Response
    {
        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');
        $cached = $state ? Cache::pull(MercadoPagoConnectionController::OAUTH_CACHE_PREFIX.$state) : null;
        $tenant = $cached['tenant_id'] ?? null ? Tenant::find($cached['tenant_id']) : null;

        if (! $code || ! $cached || ! $tenant) {
            return response('Invalid or expired Mercado Pago authorization request.', 400);
        }

        $tokenResponse = $this->adapter->exchangeCodeForToken($code, MercadoPagoConnectionController::callbackUrl(), $cached['code_verifier']);

        $tenant->execute(function () use ($tokenResponse) {
            app(ConnectMercadoPagoAccountAction::class)->execute($tokenResponse);
        });

        return redirect()->to($this->tenantSettingsUrl($tenant, ['mercadopago_connected' => '1']));
    }

    /**
     * @param  array<string, string>  $query
     */
    private function tenantSettingsUrl(Tenant $tenant, array $query): string
    {
        $scheme = request()->isSecure() ? 'https' : 'http';

        return "{$scheme}://{$tenant->domain}/settings/mercado-pago?".http_build_query($query);
    }
}
