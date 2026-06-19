<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Promotion;

class StorePromotionAction
{
    /**
     * @param  array{
     *     name: string,
     *     code: string|null,
     *     sale_price: float|int|null,
     *     is_active: bool,
     *     items: array<int, array{product_id: string, quantity: int}>
     * }  $data
     */
    public function execute(array $data): Promotion
    {
        $items = $data['items'];
        unset($data['items']);

        $promotion = Promotion::create($data);

        $this->syncItems($promotion, $items);

        return $promotion->load('items.product');
    }

    private function syncItems(Promotion $promotion, array $items): void
    {
        $promotion->items()->delete();

        $promotion->items()->createMany(
            array_map(fn (array $item) => [
                'product_id' => Product::where('uuid', $item['product_id'])->value('id'),
                'quantity' => $item['quantity'],
            ], $items)
        );
    }
}
