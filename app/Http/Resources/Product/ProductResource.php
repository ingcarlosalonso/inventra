<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\ProductPresentation\ProductPresentationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'product_type' => $this->whenLoaded('productType', fn () => [
                'id' => $this->productType->uuid,
                'name' => $this->productType->name,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => $this->currency ? [
                'id' => $this->currency->uuid,
                'name' => $this->currency->name,
                'symbol' => $this->currency->symbol,
            ] : null),
            'presentations' => $this->whenLoaded(
                'productPresentations',
                fn () => ProductPresentationResource::collection($this->productPresentations)
            ),
            'barcodes' => $this->whenLoaded('barcodes', fn () => $this->barcodes->pluck('barcode')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
