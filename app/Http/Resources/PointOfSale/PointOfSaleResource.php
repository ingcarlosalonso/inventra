<?php

namespace App\Http\Resources\PointOfSale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointOfSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'number' => $this->number,
            'name' => $this->name,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'auto_open_time' => $this->auto_open_time,
            'auto_close_time' => $this->auto_close_time,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
