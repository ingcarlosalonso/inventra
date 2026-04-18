<?php

namespace App\Http\Resources\Sale;

use App\Http\Resources\Payment\PaymentResource;
use App\Http\Resources\SaleItem\SaleItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'subtotal' => (float) $this->subtotal,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'paid_amount' => (float) ($this->payments_sum_amount ?? ($this->relationLoaded('payments') ? $this->payments->sum('amount') : 0)),
            'notes' => $this->notes,
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->uuid,
                'name' => $this->client->name,
            ] : null),
            'point_of_sale' => $this->whenLoaded('pointOfSale', fn () => [
                'id' => $this->pointOfSale->uuid,
                'name' => $this->pointOfSale->name,
            ]),
            'sale_state' => $this->whenLoaded('saleState', fn () => [
                'id' => $this->saleState->uuid,
                'name' => $this->saleState->name,
                'color' => $this->saleState->color ?? null,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => $this->currency ? [
                'id' => $this->currency->uuid,
                'name' => $this->currency->name,
                'code' => $this->currency->code,
            ] : null),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->uuid,
                'name' => $this->user->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => SaleItemResource::collection($this->items)),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
