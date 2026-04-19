<?php

namespace App\Http\Resources\ProductMovement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->uuid,
                'name' => $this->product->name,
            ]),
            'presentation' => $this->whenLoaded('productPresentation', fn () => [
                'id' => $this->productPresentation->uuid,
                'name' => $this->productPresentation->presentation?->name,
            ]),
            'type' => $this->whenLoaded('productMovementType', fn () => [
                'id' => $this->productMovementType->uuid,
                'name' => $this->productMovementType->name,
                'is_income' => $this->productMovementType->is_income,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->uuid,
                'name' => $this->user->name,
            ]),
        ];
    }
}
