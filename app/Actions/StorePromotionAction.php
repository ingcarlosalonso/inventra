<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Promotion;

class StorePromotionAction
{
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
