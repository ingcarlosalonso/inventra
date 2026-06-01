<?php

namespace App\Http\Resources\ProductPresentation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPresentationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'price' => $this->price,
            'stock' => $this->stock,
            'min_stock' => $this->min_stock,
            'is_active' => $this->is_active,
            'presentation' => $this->whenLoaded('presentation', fn () => [
                'id' => $this->presentation->uuid,
                'display' => $this->presentation->relationLoaded('presentationType')
                    ? (float) $this->presentation->quantity.' '.$this->presentation->presentationType->abbreviation
                    : null,
                'quantity' => (float) $this->presentation->quantity,
                'presentation_type' => $this->presentation->relationLoaded('presentationType') ? [
                    'id' => $this->presentation->presentationType->uuid,
                    'name' => $this->presentation->presentationType->name,
                    'abbreviation' => $this->presentation->presentationType->abbreviation,
                ] : null,
            ]),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
