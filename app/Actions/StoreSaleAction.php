<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\Client;
use App\Models\Currency;
use App\Models\DailyCash;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\SaleState;
use Illuminate\Support\Facades\DB;

class StoreSaleAction
{
    public function __construct(
        private readonly BuildSaleItemsData $buildSaleItemsData
    ) {}

    /**
     * @param  array{
     *     client_id: string|null,
     *     point_of_sale_id: string,
     *     sale_state_id: string|null,
     *     daily_cash_id: string|null,
     *     currency_id: string|null,
     *     notes: string|null,
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
    public function execute(array $data, int $userId): Sale
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId): Sale {
            // Resolve UUIDs to IDs
            $clientId = isset($data['client_id'])
                ? Client::where('uuid', $data['client_id'])->value('id')
                : null;

            $pointOfSaleId = PointOfSale::where('uuid', $data['point_of_sale_id'])->value('id');

            $saleStateId = isset($data['sale_state_id'])
                ? SaleState::where('uuid', $data['sale_state_id'])->value('id')
                : SaleState::where('is_default', true)->value('id');

            $dailyCashId = isset($data['daily_cash_id'])
                ? DailyCash::where('uuid', $data['daily_cash_id'])->value('id')
                : DailyCash::where('point_of_sale_id', $pointOfSaleId)->where('is_closed', false)->orderByDesc('id')->value('id');

            $currencyId = isset($data['currency_id'])
                ? Currency::where('uuid', $data['currency_id'])->value('id')
                : null;

            // Build and calculate items (presentations keyed by UUID)
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

            // Create sale
            $sale = Sale::create([
                'client_id' => $clientId,
                'point_of_sale_id' => $pointOfSaleId,
                'sale_state_id' => $saleStateId,
                'daily_cash_id' => $dailyCashId,
                'currency_id' => $currencyId,
                'user_id' => $userId,
                'subtotal' => $built['subtotal'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'discount_amount' => $built['discount_amount'],
                'total' => $built['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Create items and decrement stock
            foreach ($data['items'] as $index => $inputItem) {
                $builtItem = $built['items'][$index];
                $sale->items()->create($builtItem);

                $presentation = $built['presentations']->get($inputItem['product_presentation_id']);
                if ($presentation) {
                    $presentation->decrement('stock', $builtItem['quantity']);
                }
            }

            // Create payments
            foreach ($data['payments'] as $paymentData) {
                $paymentMethodId = PaymentMethod::where('uuid', $paymentData['payment_method_id'])->value('id');

                $paymentCurrencyId = isset($paymentData['currency_id'])
                    ? Currency::where('uuid', $paymentData['currency_id'])->value('id')
                    : null;

                Payment::create([
                    'payable_type' => 'sale',
                    'payable_id' => $sale->id,
                    'payment_method_id' => $paymentMethodId,
                    'currency_id' => $paymentCurrencyId,
                    'daily_cash_id' => $dailyCashId,
                    'amount' => (float) $paymentData['amount'],
                    'exchange_rate' => isset($paymentData['exchange_rate']) ? (float) $paymentData['exchange_rate'] : null,
                    'notes' => $paymentData['notes'] ?? null,
                ]);
            }

            return $sale->load([
                'client',
                'pointOfSale',
                'saleState',
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
