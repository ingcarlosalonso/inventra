<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMercadoPagoWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $dataId = (string) $request->query('data_id', '');
        $requestId = (string) $request->header('x-request-id', '');
        $signatureHeader = (string) $request->header('x-signature', '');

        if (! $dataId || ! $requestId || ! $this->hasValidSignature($signatureHeader, $dataId, $requestId)) {
            Log::warning('Rejected Mercado Pago webhook with invalid or missing signature.');

            return response('', 401);
        }

        $userId = (string) $request->input('user_id', '');
        $type = (string) $request->input('type', $request->input('topic', ''));

        // Point/POS charges are created through the Orders API, so Mercado Pago only ever
        // notifies them under the "order" topic — "payment" notifications carry a payment id,
        // not an order id, which ProcessMercadoPagoWebhookAction has no way to resolve.
        if ($userId && $type === 'order') {
            ProcessMercadoPagoWebhookJob::dispatch($userId, $dataId);
        }

        return response('', 204);
    }

    private function hasValidSignature(string $signatureHeader, string $dataId, string $requestId): bool
    {
        $secret = (string) config('services.mercadopago.webhook_secret');

        if (! $secret || ! $signatureHeader) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        $ts = $parts['ts'] ?? null;
        $signature = $parts['v1'] ?? null;

        if (! $ts || ! $signature) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $signature);
    }
}
