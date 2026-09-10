<?php

namespace App\Actions\MercadoPago;

use App\Adapters\MercadoPagoAdapter;
use App\Enums\MercadoPagoOrderStatus;
use App\Exceptions\MercadoPagoException;
use App\Models\MercadoPagoCredential;
use App\Models\MercadoPagoOrder;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Scopes\ByUuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePosnetChargeAction
{
    public function __construct(private MercadoPagoAdapter $adapter) {}

    public function execute(string $payableType, string $payableUuid, float $amount): MercadoPagoOrder
    {
        $credential = MercadoPagoCredential::query()->first();

        if (! $credential) {
            throw MercadoPagoException::notConnected();
        }

        if (! $credential->payment_method_id) {
            throw MercadoPagoException::paymentMethodNotConfigured();
        }

        return DB::connection('tenant')->transaction(function () use ($payableType, $payableUuid, $amount, $credential) {
            // Lock the payable row so two concurrent charge attempts (double-click, retry
            // after timeout) can't both read the same pending balance and both push a charge.
            $payable = match ($payableType) {
                'sale' => Sale::query()->with('pointOfSale')->withScopes(new ByUuid($payableUuid))->lockForUpdate()->firstOrFail(),
                'order' => Order::query()->with('pointOfSale')->withScopes(new ByUuid($payableUuid))->lockForUpdate()->firstOrFail(),
            };

            $pointOfSale = $payable->pointOfSale;

            if (! $pointOfSale?->mercado_pago_terminal_id) {
                throw MercadoPagoException::terminalNotConfigured();
            }

            $inFlightStatuses = array_filter(
                MercadoPagoOrderStatus::cases(),
                fn (MercadoPagoOrderStatus $status) => ! $status->isFinal(),
            );

            // Charges already pushed to the terminal but not yet confirmed haven't created a
            // Payment row yet — without subtracting them here, a second charge could be created
            // for the same balance while the first is still awaiting the customer's card.
            $inFlightAmount = (float) MercadoPagoOrder::query()
                ->where('payable_type', $payableType)
                ->where('payable_id', $payable->id)
                ->whereIn('status', $inFlightStatuses)
                ->sum('amount');

            $pendingBalance = (float) $payable->total - (float) $payable->payments()->sum('amount') - $inFlightAmount;

            if ($amount > $pendingBalance + 0.01) {
                throw MercadoPagoException::amountExceedsPendingBalance();
            }

            $externalReference = (string) Str::uuid();

            $response = $this->adapter->createOrder(
                $credential->access_token,
                $pointOfSale->mercado_pago_terminal_id,
                $externalReference,
                $amount,
            );

            $status = MercadoPagoOrderStatus::tryFrom($response['status'] ?? 'created') ?? MercadoPagoOrderStatus::Created;

            return MercadoPagoOrder::create([
                'payable_type' => $payableType,
                'payable_id' => $payable->id,
                'point_of_sale_id' => $pointOfSale->id,
                'payment_method_id' => $credential->payment_method_id,
                'mercado_pago_order_id' => (string) $response['id'],
                'external_reference' => $externalReference,
                'status' => $status,
                'amount' => $amount,
            ]);
        });
    }
}
