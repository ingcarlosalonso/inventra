<?php

namespace App\Services;

use App\Actions\BuildSaleItemsData;
use App\Enums\SaleItemType;
use App\Exceptions\InsufficientStockException;
use App\Models\Client;
use App\Models\CompositeProduct;
use App\Models\Courier;
use App\Models\Currency;
use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\ByPointOfSale as DailyCashByPointOfSale;
use App\Models\DailyCash\Scopes\Open;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\OrderState\Scopes\IsDefault as OrderStateIsDefault;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\ProductPresentation;
use App\Models\Promotion;
use App\Models\Scopes\ByUuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProcessOrderService
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
     *     }>,
     * }  $data
     */
    public function execute(array $data, int $userId): Order
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId): Order {
            $clientId = isset($data['client_id'])
                ? Client::query()->withScopes(new ByUuid($data['client_id']))->value('id')
                : null;

            $courierId = isset($data['courier_id'])
                ? Courier::query()->withScopes(new ByUuid($data['courier_id']))->value('id')
                : null;

            $orderStateId = isset($data['order_state_id'])
                ? OrderState::query()->withScopes(new ByUuid($data['order_state_id']))->value('id')
                : OrderState::query()->withScopes(new OrderStateIsDefault)->value('id');

            $pointOfSaleId = isset($data['point_of_sale_id'])
                ? PointOfSale::query()->withScopes(new ByUuid($data['point_of_sale_id']))->value('id')
                : null;

            $dailyCashId = $pointOfSaleId
                ? DailyCash::query()
                    ->withScopes([new DailyCashByPointOfSale($pointOfSaleId), new Open])
                    ->orderByDesc('id')
                    ->value('id')
                : null;

            $currencyId = isset($data['currency_id'])
                ? Currency::query()->withScopes(new ByUuid($data['currency_id']))->value('id')
                : null;

            $built = $this->buildSaleItemsData->execute(
                $data['items'],
                $data['discount_type'] ?? null,
                $data['discount_value'] ?? null,
            );

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

            foreach ($built['items'] as $builtItem) {
                $order->items()->create($builtItem);
            }

            foreach ($stockOps as $op) {
                $op['presentation']->decrement('stock', $op['quantity']);
            }

            foreach ($data['payments'] ?? [] as $paymentData) {
                $paymentMethodId = PaymentMethod::query()->withScopes(new ByUuid($paymentData['payment_method_id']))->value('id');

                $paymentCurrencyId = isset($paymentData['currency_id'])
                    ? Currency::query()->withScopes(new ByUuid($paymentData['currency_id']))->value('id')
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

            $order->load(['client', 'courier', 'orderState', 'pointOfSale', 'currency', 'user', 'items', 'payments.paymentMethod', 'payments.currency']);

            $order->items->loadMorph('saleable', [
                ProductPresentation::class => ['product', 'presentation'],
                CompositeProduct::class => [],
                Promotion::class => [],
            ]);

            return $order;
        });
    }

    /**
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
