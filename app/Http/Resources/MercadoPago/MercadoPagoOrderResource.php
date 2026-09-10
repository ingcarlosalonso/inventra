<?php

namespace App\Http\Resources\MercadoPago;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MercadoPagoOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status->value,
            'is_final' => $this->status->isFinal(),
            'is_successful' => $this->status->isSuccessful(),
            'amount' => (float) $this->amount,
            'failure_detail' => $this->failure_detail,
            'created_at' => $this->created_at->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
        ];
    }
}
