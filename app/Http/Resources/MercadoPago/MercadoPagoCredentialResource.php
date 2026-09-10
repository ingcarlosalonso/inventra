<?php

namespace App\Http\Resources\MercadoPago;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MercadoPagoCredentialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'connected' => true,
            'public_key' => $this->public_key,
            'live_mode' => $this->live_mode,
            'connected_at' => $this->connected_at?->toISOString(),
        ];
    }
}
