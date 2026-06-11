<?php

namespace App\Http\Resources\CompositeProduct;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompositeProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'items' => $this->whenLoaded(
                'items',
                fn () => CompositeProductItemResource::collection($this->items->load('product'))
            ),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
