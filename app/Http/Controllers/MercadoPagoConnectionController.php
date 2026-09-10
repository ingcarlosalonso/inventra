<?php

namespace App\Http\Controllers;

use App\Adapters\MercadoPagoAdapter;
use App\Http\Requests\MercadoPago\UpdateMercadoPagoSettingsRequest;
use App\Http\Resources\MercadoPago\MercadoPagoCredentialResource;
use App\Models\MercadoPagoCredential;
use App\Models\PaymentMethod;
use App\Models\Scopes\ByUuid;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MercadoPagoConnectionController extends Controller
{
    public const OAUTH_CACHE_PREFIX = 'mercadopago_oauth_state:';

    public function __construct(private MercadoPagoAdapter $adapter) {}

    /**
     * Mercado Pago OAuth apps only accept a single, static redirect_uri — it cannot vary
     * per tenant subdomain. It must live on the central domain; the tenant that initiated
     * the flow travels through the cached `state` instead (see Central\MercadoPagoOAuthCallbackController).
     */
    public static function callbackUrl(): string
    {
        $scheme = request()->isSecure() ? 'https' : 'http';

        return "{$scheme}://".config('app.central_domain').'/oauth/mercadopago/callback';
    }

    public function show(): JsonResponse
    {
        $credential = MercadoPagoCredential::query()->with('paymentMethod')->first();

        if (! $credential) {
            return response()->json(['data' => ['connected' => false]]);
        }

        return MercadoPagoCredentialResource::make($credential)->response();
    }

    public function redirectUrl(Request $request): JsonResponse
    {
        $state = Str::random(40);
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        Cache::put(self::OAUTH_CACHE_PREFIX.$state, [
            'code_verifier' => $codeVerifier,
            'tenant_id' => Tenant::current()?->id,
        ], now()->addMinutes(10));

        $url = $this->adapter->authorizationUrl(self::callbackUrl(), $state, $codeChallenge);

        return response()->json(['url' => $url]);
    }

    public function update(UpdateMercadoPagoSettingsRequest $request): JsonResponse
    {
        $credential = MercadoPagoCredential::query()->firstOrFail();

        $paymentMethodId = PaymentMethod::query()->withScopes(new ByUuid($request->string('payment_method_id')))->value('id');

        $credential->update(['payment_method_id' => $paymentMethodId]);

        return MercadoPagoCredentialResource::make($credential->fresh(['paymentMethod']))->response();
    }

    public function destroy(): JsonResponse
    {
        $credential = MercadoPagoCredential::query()->first();

        if ($credential) {
            try {
                $this->adapter->revokeAuthorization($credential->access_token, $credential->mercado_pago_user_id);
            } catch (Throwable $e) {
                // Best effort: the local disconnect must succeed even if Mercado Pago's
                // API is unreachable or the token was already revoked on their side.
                Log::warning('Failed to revoke Mercado Pago authorization on disconnect.', [
                    'message' => $e->getMessage(),
                ]);
            }

            $credential->delete();
        }

        Tenant::current()?->update(['mercado_pago_user_id' => null]);

        return response()->json([], 204);
    }
}
