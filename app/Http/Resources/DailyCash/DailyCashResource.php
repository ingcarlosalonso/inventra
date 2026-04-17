<?php

namespace App\Http\Resources\DailyCash;

use App\Http\Resources\CashMovement\CashMovementResource;
use App\Http\Resources\PointOfSale\PointOfSaleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyCashResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'point_of_sale' => PointOfSaleResource::make($this->whenLoaded('pointOfSale')),
            'point_of_sale_id' => $this->pointOfSale?->uuid,
            'user_id' => $this->user_id,
            'opening_balance' => $this->opening_balance,
            'closing_balance' => $this->closing_balance,
            'opened_at' => $this->opened_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'is_closed' => $this->is_closed,
            'notes' => $this->notes,
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name]),
            'cash_movements' => CashMovementResource::collection($this->whenLoaded('cashMovements')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
