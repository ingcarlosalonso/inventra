<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'amount' => (float) $this->amount,
            'exchange_rate' => $this->exchange_rate ? (float) $this->exchange_rate : null,
            'notes' => $this->notes,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => [
                'id' => $this->paymentMethod->uuid,
                'name' => $this->paymentMethod->name,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => $this->currency ? [
                'id' => $this->currency->uuid,
                'name' => $this->currency->name,
                'code' => $this->currency->code,
            ] : null),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
