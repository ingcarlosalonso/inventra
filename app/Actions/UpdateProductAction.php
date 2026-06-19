<?php

namespace App\Actions;

use App\Models\Brand;
use App\Models\Currency;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductType;

class UpdateProductAction
{
    /**
     * @param  array{
     *     name: string,
     *     description: string|null,
     *     brand_id: string|null,
     *     product_type_id: string,
     *     currency_id: string|null,
     *     is_active: bool,
     *     presentations: array<int, array{
     *         presentation_id: string,
     *         price: float|int,
     *         min_stock: float|int,
     *         barcodes: string[]|null
     *     }>
     * }  $data
     */
    public function execute(Product $product, array $data): Product
    {
        $presentations = $data['presentations'];
        unset($data['presentations']);

        $data['brand_id'] = $this->resolveId(Brand::class, $data['brand_id'] ?? null);
        $data['product_type_id'] = $this->resolveId(ProductType::class, $data['product_type_id']);
        $data['currency_id'] = $this->resolveId(Currency::class, $data['currency_id'] ?? null);

        $product->update($data);

        $this->syncPresentations($product, $presentations);

        return $product->fresh()->load(['brand', 'productType', 'barcodes', 'currency', 'productPresentations.presentation.presentationType', 'productPresentations.barcodes']);
    }

    private function syncPresentations(Product $product, array $presentations): void
    {
        // Delete barcodes before soft-deleting presentations (DB cascade only fires on hard delete)
        $product->productPresentations->each(fn ($pp) => $pp->barcodes()->delete());
        $product->productPresentations()->delete();

        foreach ($presentations as $item) {
            $pp = $product->productPresentations()->create([
                'presentation_id' => Presentation::where('uuid', $item['presentation_id'])->value('id'),
                'price' => $item['price'],
                'min_stock' => $item['min_stock'],
                'stock' => $item['stock'] ?? 0,
            ]);

            if (! empty($item['barcodes'])) {
                $pp->barcodes()->createMany(
                    array_map(fn (string $bc) => ['barcode' => $bc], $item['barcodes'])
                );
            }
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
