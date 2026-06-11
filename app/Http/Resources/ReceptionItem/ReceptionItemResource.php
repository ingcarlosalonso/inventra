<?php

namespace App\Http\Resources\ReceptionItem;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'quantity' => (float) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'total' => (float) $this->total,
            'product_presentation' => $this->whenLoaded('productPresentation', fn () => [
                'id' => $this->productPresentation->uuid,
                'stock' => (float) $this->productPresentation->stock,
                'presentation' => $this->productPresentation->relationLoaded('presentation') ? [
                    'id' => $this->productPresentation->presentation->uuid,
                    'display' => $this->productPresentation->presentation->relationLoaded('presentationType')
                        ? (float) $this->productPresentation->presentation->quantity.' '.$this->productPresentation->presentation->presentationType->abbreviation
                        : null,
                ] : null,
                'product' => $this->productPresentation->relationLoaded('product') ? [
                    'id' => $this->productPresentation->product->uuid,
                    'name' => $this->productPresentation->product->name,
                ] : null,
            ]),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
