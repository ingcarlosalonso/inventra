<?php

namespace App\Http\Resources\SaleState;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'color' => $this->color,
            'is_default' => $this->is_default,
            'is_final_state' => $this->is_final_state,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
