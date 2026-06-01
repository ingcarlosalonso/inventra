<?php

namespace App\Http\Resources\Quote;

use App\Http\Resources\QuoteItem\QuoteItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'subtotal' => (float) $this->subtotal,
            'discount_type' => $this->discount_type?->value,
            'discount_value' => (float) $this->discount_value,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'starts_at' => $this->starts_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'is_converted' => $this->isConverted(),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->uuid,
                'name' => $this->client->name,
            ] : null),
            'currency' => $this->whenLoaded('currency', fn () => $this->currency ? [
                'id' => $this->currency->uuid,
                'name' => $this->currency->name,
                'code' => $this->currency->code,
            ] : null),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->uuid,
                'name' => $this->user->name,
            ]),
            'sale' => $this->whenLoaded('sale', fn () => $this->sale ? [
                'id' => $this->sale->uuid,
            ] : null),
            'items' => $this->whenLoaded('items', fn () => QuoteItemResource::collection($this->items)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
