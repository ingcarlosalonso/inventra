<?php

namespace App\Http\Resources\CashMovement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'daily_cash_id' => $this->daily_cash_id,
            'cash_movement_type_id' => $this->cash_movement_type_id,
            'cash_movement_type' => $this->whenLoaded('cashMovementType', fn () => [
                'id' => $this->cashMovementType->uuid,
                'name' => $this->cashMovementType->name,
                'direction' => $this->cashMovementType->direction,
            ]),
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
