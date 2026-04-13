<?php

namespace App\Http\Resources\Reception;

use App\Http\Resources\ReceptionItem\ReceptionItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'supplier_invoice' => $this->supplier_invoice,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'received_at' => $this->received_at->toDateString(),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier ? [
                'id' => $this->supplier->uuid,
                'name' => $this->supplier->name,
            ] : null),
            'daily_cash' => $this->whenLoaded('dailyCash', fn () => $this->dailyCash ? [
                'id' => $this->dailyCash->uuid,
            ] : null),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->uuid,
                'name' => $this->user->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => ReceptionItemResource::collection($this->items)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
