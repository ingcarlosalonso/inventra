<?php

namespace App\Actions\MercadoPago;

use App\Adapters\MercadoPagoAdapter;
use App\Enums\MercadoPagoOrderStatus;
use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\ByPointOfSale as DailyCashByPointOfSale;
use App\Models\DailyCash\Scopes\Open;
use App\Models\MercadoPagoCredential;
use App\Models\MercadoPagoOrder;
use App\Models\MercadoPagoOrder\Scopes\ByMercadoPagoOrderId;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMercadoPagoWebhookAction
{
    public function __construct(private MercadoPagoAdapter $adapter) {}

    public function execute(string $mercadoPagoOrderId): void
    {
        $order = MercadoPagoOrder::query()->withScopes(new ByMercadoPagoOrderId($mercadoPagoOrderId))->first();

        if (! $order) {
            Log::warning("Mercado Pago webhook received for unknown order [{$mercadoPagoOrderId}].");

            return;
        }

        $credential = MercadoPagoCredential::query()->first();

        if (! $credential) {
            Log::warning("Mercado Pago webhook received for order [{$mercadoPagoOrderId}] but tenant has no credential.");

            return;
        }

        $response = $this->adapter->getOrder($credential->access_token, $mercadoPagoOrderId);
        $status = MercadoPagoOrderStatus::tryFrom($response['status'] ?? '');

        if (! $status) {
            Log::warning("Mercado Pago webhook received an unrecognized status [{$response['status']}] for order [{$mercadoPagoOrderId}].");

            return;
        }

        DB::connection('tenant')->transaction(function () use ($mercadoPagoOrderId, $response, $status) {
            // Re-fetch under a row lock so two concurrent deliveries of the same webhook
            // (Mercado Pago retries notifications) can't both pass the "not yet paid" check
            // below and register the payment twice.
            $order = MercadoPagoOrder::query()->withScopes(new ByMercadoPagoOrderId($mercadoPagoOrderId))->lockForUpdate()->first();

            if (! $order) {
                return;
            }

            $order->status = $status;
            $order->failure_detail = $response['status_detail'] ?? null;

            if ($status->isFinal()) {
                $order->processed_at = now();
            }

            if ($status->isSuccessful() && ! $order->mercado_pago_payment_id) {
                $order->mercado_pago_payment_id = (string) ($response['transactions']['payments'][0]['id'] ?? '');
                $this->registerPayment($order);
            }

            $order->save();
        });
    }

    private function registerPayment(MercadoPagoOrder $order): void
    {
        $payable = $order->payable;

        $dailyCashId = DailyCash::query()
            ->withScopes([new DailyCashByPointOfSale($order->point_of_sale_id), new Open])
            ->orderByDesc('id')
            ->value('id');

        Payment::create([
            'payable_type' => $order->payable_type,
            'payable_id' => $payable->id,
            'payment_method_id' => $order->payment_method_id,
            'daily_cash_id' => $dailyCashId,
            'amount' => $order->amount,
            'notes' => __('mercadopago.payment_note', ['order' => $order->mercado_pago_order_id]),
        ]);
    }
}
