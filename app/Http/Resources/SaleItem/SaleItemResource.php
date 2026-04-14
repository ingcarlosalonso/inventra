<?php

namespace App\Http\Resources\SaleItem;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount_type' => $this->discount_type?->value,
            'discount_value' => (float) $this->discount_value,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'product_presentation' => $this->whenLoaded('productPresentation', fn () => $this->productPresentation ? [
                'id' => $this->productPresentation->uuid,
                'product' => $this->productPresentation->relationLoaded('product') ? [
                    'id' => $this->productPresentation->product->uuid,
                    'name' => $this->productPresentation->product->name,
                ] : null,
                'presentation' => $this->productPresentation->relationLoaded('presentation') ? [
                    'id' => $this->productPresentation->presentation->uuid,
                    'name' => $this->productPresentation->presentation->name,
                ] : null,
            ] : null),
        ];
    }
}
