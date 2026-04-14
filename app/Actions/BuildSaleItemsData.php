<?php

namespace App\Actions;

use App\Enums\DiscountType;
use App\Models\ProductPresentation;
use Illuminate\Database\Eloquent\Collection;

class BuildSaleItemsData
{
    /**
     * Calculate sale items data from raw input.
     *
     * @param  array<int, array{
     *     product_presentation_id: string,
     *     description: string,
     *     quantity: float|string,
     *     unit_price: float|string,
     *     discount_type: string|null,
     *     discount_value: float|string|null,
     * }>  $items
     * @return array{
     *     items: array<int, array{
     *         product_presentation_id: int,
     *         description: string,
     *         quantity: float,
     *         unit_price: float,
     *         discount_type: string|null,
     *         discount_value: float,
     *         discount_amount: float,
     *         total: float,
     *     }>,
     *     presentations: Collection,
     *     subtotal: float,
     *     discount_amount: float,
     *     total: float,
     * }
     */
    public function execute(array $items, ?string $discountType, float|string|null $discountValue): array
    {
        $uuids = array_column($items, 'product_presentation_id');

        $presentations = ProductPresentation::whereIn('uuid', $uuids)
            ->with(['product', 'presentation'])
            ->get()
            ->keyBy('uuid');

        $subtotal = 0;
        $builtItems = [];

        foreach ($items as $item) {
            $presentation = $presentations->get($item['product_presentation_id']);
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            $itemDiscountType = isset($item['discount_type']) ? $item['discount_type'] : null;
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

            $builtItems[] = [
                'product_presentation_id' => $presentation->id,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_type' => $itemDiscountType,
                'discount_value' => $itemDiscountValue,
                'discount_amount' => $itemDiscountAmount,
                'total' => $lineTotal,
            ];
        }

        $subtotal = round($subtotal, 2);
        $saleDiscountAmount = 0.0;
        $discountValue = (float) ($discountValue ?? 0);

        if ($discountType === DiscountType::Percentage->value && $discountValue > 0) {
            $saleDiscountAmount = round($subtotal * $discountValue / 100, 2);
        } elseif ($discountType === DiscountType::Fixed->value && $discountValue > 0) {
            $saleDiscountAmount = min(round($discountValue, 2), $subtotal);
        }

        $total = round($subtotal - $saleDiscountAmount, 2);

        return [
            'items' => $builtItems,
            'presentations' => $presentations,
            'subtotal' => $subtotal,
            'discount_amount' => $saleDiscountAmount,
            'total' => $total,
        ];
    }
}
