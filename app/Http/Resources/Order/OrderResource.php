<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\OrderItem\OrderItemResource;
use App\Http\Resources\Payment\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'address' => $this->address,
            'notes' => $this->notes,
            'requires_delivery' => $this->requires_delivery,
            'delivery_date' => $this->delivery_date?->toDateString(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'subtotal' => (float) $this->subtotal,
            'discount_type' => $this->discount_type?->value,
            'discount_value' => (float) $this->discount_value,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'paid_amount' => (float) ($this->payments_sum_amount ?? ($this->relationLoaded('payments') ? $this->payments->sum('amount') : 0)),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->uuid,
                'name' => $this->client->name,
            ] : null),
            'courier' => $this->whenLoaded('courier', fn () => $this->courier ? [
                'id' => $this->courier->uuid,
                'name' => $this->courier->name,
                'phone' => $this->courier->phone,
            ] : null),
            'order_state' => $this->whenLoaded('orderState', fn () => [
                'id' => $this->orderState->uuid,
                'name' => $this->orderState->name,
                'color' => $this->orderState->color ?? null,
            ]),
            'point_of_sale' => $this->whenLoaded('pointOfSale', fn () => $this->pointOfSale ? [
                'id' => $this->pointOfSale->uuid,
                'name' => $this->pointOfSale->name,
            ] : null),
            'sale' => $this->whenLoaded('sale', fn () => $this->sale ? [
                'id' => $this->sale->uuid,
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
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
