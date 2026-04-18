<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\Client;
use App\Models\Courier;
use App\Models\Currency;
use App\Models\DailyCash;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use Illuminate\Support\Facades\DB;

class StoreOrderAction
{
    public function __construct(
        private readonly BuildSaleItemsData $buildSaleItemsData
    ) {}

    /**
     * @param  array{
     *     client_id: string|null,
     *     courier_id: string|null,
     *     order_state_id: string|null,
     *     point_of_sale_id: string|null,
     *     sale_id: int|null,
     *     currency_id: string|null,
     *     address: string|null,
     *     notes: string|null,
     *     requires_delivery: bool,
     *     delivery_date: string|null,
     *     scheduled_at: string|null,
     *     discount_type: string|null,
     *     discount_value: float|string|null,
     *     items: array<int, array{
     *         product_presentation_id: string,
     *         description: string,
     *         quantity: float|string,
     *         unit_price: float|string,
     *         discount_type: string|null,
     *         discount_value: float|string|null,
     *     }>,
     *     payments: array<int, array{
     *         payment_method_id: string,
     *         currency_id: string|null,
     *         amount: float|string,
     *         exchange_rate: float|string|null,
     *         notes: string|null,
     *     }>,
     * }  $data
     */
    public function execute(array $data, int $userId): Order
    {
        return DB::transaction(function () use ($data, $userId): Order {
            $clientId = isset($data['client_id'])
                ? Client::where('uuid', $data['client_id'])->value('id')
                : null;

            $courierId = isset($data['courier_id'])
                ? Courier::where('uuid', $data['courier_id'])->value('id')
                : null;

            $orderStateId = isset($data['order_state_id'])
                ? OrderState::where('uuid', $data['order_state_id'])->value('id')
                : OrderState::where('is_default', true)->value('id');

            $pointOfSaleId = isset($data['point_of_sale_id'])
                ? PointOfSale::where('uuid', $data['point_of_sale_id'])->value('id')
                : null;

            $dailyCashId = $pointOfSaleId
                ? DailyCash::where('point_of_sale_id', $pointOfSaleId)->where('is_closed', false)->orderByDesc('id')->value('id')
                : null;

            $currencyId = isset($data['currency_id'])
                ? Currency::where('uuid', $data['currency_id'])->value('id')
                : null;

            $built = $this->buildSaleItemsData->execute(
                $data['items'],
                $data['discount_type'] ?? null,
                $data['discount_value'] ?? null,
            );

            // Validate stock availability before any writes
            foreach ($data['items'] as $index => $inputItem) {
                $presentation = $built['presentations']->get($inputItem['product_presentation_id']);
                $builtItem = $built['items'][$index];

                if ($presentation && (float) $presentation->stock < $builtItem['quantity']) {
                    throw new InsufficientStockException(
                        $presentation->product->name.' - '.$presentation->presentation->name,
                        $builtItem['quantity'],
                        (float) $presentation->stock,
                    );
                }
            }

            $order = Order::create([
                'client_id' => $clientId,
                'courier_id' => $courierId,
                'order_state_id' => $orderStateId,
                'point_of_sale_id' => $pointOfSaleId,
                'sale_id' => $data['sale_id'] ?? null,
                'currency_id' => $currencyId,
                'user_id' => $userId,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'requires_delivery' => $data['requires_delivery'] ?? false,
                'delivery_date' => $data['delivery_date'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'subtotal' => $built['subtotal'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'discount_amount' => $built['discount_amount'],
                'total' => $built['total'],
            ]);

            // Create items and decrement stock
            foreach ($data['items'] as $index => $inputItem) {
                $builtItem = $built['items'][$index];
                $order->items()->create($builtItem);

                $presentation = $built['presentations']->get($inputItem['product_presentation_id']);
                if ($presentation) {
                    $presentation->decrement('stock', $builtItem['quantity']);
                }
            }

            // Create payments
            foreach ($data['payments'] ?? [] as $paymentData) {
                $paymentMethodId = PaymentMethod::where('uuid', $paymentData['payment_method_id'])->value('id');

                $paymentCurrencyId = isset($paymentData['currency_id'])
                    ? Currency::where('uuid', $paymentData['currency_id'])->value('id')
                    : null;

                Payment::create([
                    'payable_type' => 'order',
                    'payable_id' => $order->id,
                    'payment_method_id' => $paymentMethodId,
                    'currency_id' => $paymentCurrencyId,
                    'daily_cash_id' => $dailyCashId,
                    'amount' => (float) $paymentData['amount'],
                    'exchange_rate' => isset($paymentData['exchange_rate']) ? (float) $paymentData['exchange_rate'] : null,
                    'notes' => $paymentData['notes'] ?? null,
                ]);
            }

            return $order->load([
                'client',
                'courier',
                'orderState',
                'pointOfSale',
                'currency',
                'user',
                'items.productPresentation.product',
                'items.productPresentation.presentation',
                'payments.paymentMethod',
                'payments.currency',
            ]);
        });
    }
}
