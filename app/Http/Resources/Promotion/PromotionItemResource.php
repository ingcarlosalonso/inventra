<?php

namespace App\Http\Resources\Promotion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->uuid,
                'name' => $this->product->name,
                'code' => $this->product->code ?? null,
            ]),
        ];
    }
}
