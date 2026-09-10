<?php

namespace App\Http\Controllers;

use App\Actions\MercadoPago\CreatePosnetChargeAction;
use App\Actions\MercadoPago\ProcessMercadoPagoWebhookAction;
use App\Http\Requests\MercadoPago\StoreMercadoPagoChargeRequest;
use App\Http\Resources\MercadoPago\MercadoPagoOrderResource;
use App\Models\MercadoPagoOrder;
use Illuminate\Http\JsonResponse;
use Throwable;

class MercadoPagoChargeController extends Controller
{
    public function store(StoreMercadoPagoChargeRequest $request, CreatePosnetChargeAction $action): JsonResponse
    {
        $data = $request->validated();

        $order = $action->execute($data['payable_type'], $data['payable_id'], (float) $data['amount']);

        return MercadoPagoOrderResource::make($order)->response()->setStatusCode(201);
    }

    public function show(MercadoPagoOrder $mercadoPagoOrder, ProcessMercadoPagoWebhookAction $action): JsonResponse
    {
        if (! $mercadoPagoOrder->status->isFinal()) {
            try {
                $action->execute($mercadoPagoOrder->mercado_pago_order_id);
                $mercadoPagoOrder->refresh();
            } catch (Throwable) {
                // The status poll is best-effort — the webhook remains the source of truth.
            }
        }

        return MercadoPagoOrderResource::make($mercadoPagoOrder)->response();
    }
}
