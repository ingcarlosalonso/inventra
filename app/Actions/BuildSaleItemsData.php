<?php

namespace App\Actions;

use App\Enums\DiscountType;
use App\Enums\SaleItemType;
use App\Models\CompositeProduct;
use App\Models\ProductPresentation;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;

class BuildSaleItemsData
{
    /**
     * Calculate sale items data from raw input.
     *
     * @param  array<int, array{
     *     item_type: string,
     *     saleable_id: string,
     *     description: string,
     *     quantity: float|string,
     *     unit_price: float|string,
     *     discount_type: string|null,
     *     discount_value: float|string|null,
     * }>  $items
     * @return array{
     *     items: array<int, array{
     *         saleable_type: string,
     *         saleable_id: int,
     *         product_presentation_id: int|null,
     *         description: string,
     *         quantity: float,
     *         unit_price: float,
     *         discount_type: string|null,
     *         discount_value: float,
     *         discount_amount: float,
     *         total: float,
     *     }>,
     *     presentations: Collection,
     *     composites: Collection,
     *     promotions: Collection,
     *     subtotal: float,
     *     discount_amount: float,
     *     total: float,
     * }
     */
    public function execute(array $items, ?string $discountType, float|string|null $discountValue): array
    {
        [$productUuids, $compositeUuids, $promotionUuids] = $this->partitionUuids($items);

        $presentations = ProductPresentation::whereIn('uuid', $productUuids)
            ->with(['product', 'presentation'])
            ->get()
            ->keyBy('uuid');

        $composites = CompositeProduct::whereIn('uuid', $compositeUuids)
            ->with(['items.product.productPresentations'])
            ->get()
            ->keyBy('uuid');

        $promotions = Promotion::whereIn('uuid', $promotionUuids)
            ->with(['items.product.productPresentations'])
            ->get()
            ->keyBy('uuid');

        $subtotal = 0.0;
        $builtItems = [];

        foreach ($items as $item) {
            $type = SaleItemType::from($item['item_type']);
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            $itemDiscountType = $item['discount_type'] ?? null;
            $itemDiscountValue = (float) ($item['discount_value'] ?? 0);
            $itemDiscountAmount = 0.0;
            $lineTotal = round($quantity * $unitPrice, 2);

            if ($itemDiscountType === DiscountType::Percentage->value && $itemDiscountValue > 0) {
                $itemDiscountAmount = round($lineTotal * $itemDiscountValue / 100, 2);
            } elseif ($itemDiscountType === DiscountType::Fixed->value && $itemDiscountValue > 0) {
                $itemDiscountAmount = min(round($itemDiscountValue, 2), $lineTotal);
            }

            $lineTotal = round($lineTotal - $itemDiscountAmount, 2);
            $subtotal += $lineTotal;

            $builtItems[] = array_merge(
                $this->resolveSaleableIds($type, $item['saleable_id'], $presentations, $composites, $promotions),
                [
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_type' => $itemDiscountType,
                    'discount_value' => $itemDiscountValue,
                    'discount_amount' => $itemDiscountAmount,
                    'total' => $lineTotal,
                ]
            );
        }

        $subtotal = round($subtotal, 2);
        $discountValue = (float) ($discountValue ?? 0);
        $saleDiscountAmount = 0.0;

        if ($discountType === DiscountType::Percentage->value && $discountValue > 0) {
            $saleDiscountAmount = round($subtotal * $discountValue / 100, 2);
        } elseif ($discountType === DiscountType::Fixed->value && $discountValue > 0) {
            $saleDiscountAmount = min(round($discountValue, 2), $subtotal);
        }

        return [
            'items' => $builtItems,
            'presentations' => $presentations,
            'composites' => $composites,
            'promotions' => $promotions,
            'subtotal' => $subtotal,
            'discount_amount' => $saleDiscountAmount,
            'total' => round($subtotal - $saleDiscountAmount, 2),
        ];
    }

    /** @return array{saleable_type: string, saleable_id: int, product_presentation_id: int|null} */
    private function resolveSaleableIds(
        SaleItemType $type,
        string $uuid,
        Collection $presentations,
        Collection $composites,
        Collection $promotions,
    ): array {
        return match ($type) {
            SaleItemType::Product => [
                'saleable_type' => $type->morphType(),
                'saleable_id' => $presentations->get($uuid)->id,
                'product_presentation_id' => $presentations->get($uuid)->id,
            ],
            SaleItemType::Composite => [
                'saleable_type' => $type->morphType(),
                'saleable_id' => $composites->get($uuid)->id,
                'product_presentation_id' => null,
            ],
            SaleItemType::Promotion => [
                'saleable_type' => $type->morphType(),
                'saleable_id' => $promotions->get($uuid)->id,
                'product_presentation_id' => null,
            ],
        };
    }

    /**
     * @param  array<int, array{item_type: string, saleable_id: string}>  $items
     * @return array{0: list<string>, 1: list<string>, 2: list<string>}
     */
    private function partitionUuids(array $items): array
    {
        $product = [];
        $composite = [];
        $promotion = [];

        foreach ($items as $item) {
            match (SaleItemType::from($item['item_type'])) {
                SaleItemType::Product => $product[] = $item['saleable_id'],
                SaleItemType::Composite => $composite[] = $item['saleable_id'],
                SaleItemType::Promotion => $promotion[] = $item['saleable_id'],
            };
        }

        return [$product, $composite, $promotion];
    }
}
