<?php

namespace App\Actions;

use App\Models\CompositeProduct;
use App\Models\Product;

class StoreCompositeProductAction
{
    public function execute(array $data): CompositeProduct
    {
        $items = $data['items'];
        unset($data['items']);

        $compositeProduct = CompositeProduct::create($data);

        $this->syncItems($compositeProduct, $items);

        return $compositeProduct->load('items.product');
    }

    private function syncItems(CompositeProduct $compositeProduct, array $items): void
    {
        $compositeProduct->items()->delete();

        $compositeProduct->items()->createMany(
            array_map(fn (array $item) => [
                'product_id' => Product::where('uuid', $item['product_id'])->value('id'),
                'quantity' => $item['quantity'],
            ], $items)
        );
    }
}
