<?php

namespace App\Actions;

use App\Models\Currency;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductType;

class StoreProductAction
{
    public function execute(array $data): Product
    {
        $barcodes = $data['barcodes'] ?? [];
        $presentations = $data['presentations'];
        unset($data['barcodes'], $data['presentations']);

        $data['product_type_id'] = $this->resolveId(ProductType::class, $data['product_type_id']);
        $data['currency_id'] = $this->resolveId(Currency::class, $data['currency_id'] ?? null);

        $product = Product::create($data);

        if ($barcodes) {
            $product->barcodes()->createMany(
                array_map(fn (string $barcode) => ['barcode' => $barcode], $barcodes)
            );
        }

        $this->syncPresentations($product, $presentations);

        return $product->load(['productType', 'barcodes', 'currency', 'productPresentations.presentation.presentationType']);
    }

    private function syncPresentations(Product $product, array $presentations): void
    {
        $product->productPresentations()->delete();

        foreach ($presentations as $item) {
            $product->productPresentations()->create([
                'presentation_id' => Presentation::where('uuid', $item['presentation_id'])->value('id'),
                'price' => $item['price'],
                'min_stock' => $item['min_stock'],
                'stock' => $item['stock'] ?? 0,
            ]);
        }
    }

    private function resolveId(string $model, ?string $uuid): ?int
    {
        if (! $uuid) {
            return null;
        }

        return $model::where('uuid', $uuid)->value('id');
    }
}
