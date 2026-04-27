<?php

namespace App\Actions;

use App\Enums\SaleItemType;
use App\Exceptions\InsufficientStockException;
use App\Models\Client;
use App\Models\CompositeProduct;
use App\Models\Currency;
use App\Models\DailyCash;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\ProductPresentation;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleState;
use Illuminate\Database\Eloquent\Collection;
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
     *     currency_id: string|null,
     *     notes: string|null,
     *     discount_type: string|null,
     *     discount_value: float|string|null,
     *     items: array<int, array{
     *         item_type: string,
     *         saleable_id: string,
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
     *     }>|null,
     * }  $data
     */
    public function execute(array $data, int $userId): Sale
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId): Sale {
            $clientId = isset($data['client_id'])
                ? Client::where('uuid', $data['client_id'])->value('id')
                : null;

            $pointOfSaleId = PointOfSale::where('uuid', $data['point_of_sale_id'])->value('id');

            $saleStateId = isset($data['sale_state_id'])
                ? SaleState::where('uuid', $data['sale_state_id'])->value('id')
                : SaleState::where('is_default', true)->value('id');

            $currencyId = isset($data['currency_id'])
                ? Currency::where('uuid', $data['currency_id'])->value('id')
                : null;

            $built = $this->buildSaleItemsData->execute(
                $data['items'],
                $data['discount_type'] ?? null,
                $data['discount_value'] ?? null,
            );

            // Validate stock for all components before any write
            $stockOps = $this->collectStockOperations($data['items'], $built);
            foreach ($stockOps as $op) {
                if ((float) $op['presentation']->stock < $op['quantity']) {
                    throw new InsufficientStockException(
                        $op['label'],
                        $op['quantity'],
                        (float) $op['presentation']->stock,
                    );
                }
            }

            $sale = Sale::create([
                'client_id' => $clientId,
                'point_of_sale_id' => $pointOfSaleId,
                'sale_state_id' => $saleStateId,
                'currency_id' => $currencyId,
                'user_id' => $userId,
                'subtotal' => $built['subtotal'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'discount_amount' => $built['discount_amount'],
                'total' => $built['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($built['items'] as $builtItem) {
                $sale->items()->create($builtItem);
            }

            // Decrement stock for all component presentations
            foreach ($stockOps as $op) {
                $op['presentation']->decrement('stock', $op['quantity']);
            }

            if (! empty($data['payments'])) {
                $dailyCashId = DailyCash::where('point_of_sale_id', $pointOfSaleId)
                    ->where('is_closed', false)
                    ->orderByDesc('id')
                    ->value('id');

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
            }

            $sale->load(['client', 'pointOfSale', 'saleState', 'currency', 'user', 'items', 'payments.paymentMethod', 'payments.currency']);

            $sale->items->loadMorph('saleable', [
                ProductPresentation::class => ['product', 'presentation'],
                CompositeProduct::class => [],
                Promotion::class => [],
            ]);

            return $sale;
        });
    }

    /**
     * Expand each sale item into its underlying presentation + quantity pairs for stock operations.
     *
     * @param  array<int, array{item_type: string, saleable_id: string, quantity: float|string}>  $inputItems
     * @param  array{presentations: Collection, composites: Collection, promotions: Collection, items: array<int, array{quantity: float}>}  $built
     * @return array<int, array{presentation: ProductPresentation, quantity: float, label: string}>
     */
    private function collectStockOperations(array $inputItems, array $built): array
    {
        $ops = [];

        foreach ($inputItems as $index => $inputItem) {
            $itemQty = (float) $built['items'][$index]['quantity'];
            $type = SaleItemType::from($inputItem['item_type']);

            if ($type === SaleItemType::Product) {
                $pp = $built['presentations']->get($inputItem['saleable_id']);
                if ($pp) {
                    $ops[] = [
                        'presentation' => $pp,
                        'quantity' => $itemQty,
                        'label' => $pp->product->name.' - '.$pp->presentation->name,
                    ];
                }
            } elseif ($type === SaleItemType::Composite) {
                $composite = $built['composites']->get($inputItem['saleable_id']);
                foreach ($composite?->items ?? [] as $component) {
                    $pp = $component->product->productPresentations->first();
                    if ($pp) {
                        $ops[] = [
                            'presentation' => $pp,
                            'quantity' => $itemQty * $component->quantity,
                            'label' => $composite->name.' › '.$component->product->name,
                        ];
                    }
                }
            } elseif ($type === SaleItemType::Promotion) {
                $promotion = $built['promotions']->get($inputItem['saleable_id']);
                foreach ($promotion?->items ?? [] as $promoItem) {
                    $pp = $promoItem->product->productPresentations->first();
                    if ($pp) {
                        $ops[] = [
                            'presentation' => $pp,
                            'quantity' => $itemQty * $promoItem->quantity,
                            'label' => $promotion->name.' › '.$promoItem->product->name,
                        ];
                    }
                }
            }
        }

        return $ops;
    }
}
