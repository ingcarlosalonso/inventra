<?php

namespace App\Http\Resources\ProductType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent?->uuid,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->uuid,
                'name' => $this->parent->name,
            ]),
            'children' => ProductTypeResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
