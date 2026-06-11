<?php

namespace App\Http\Resources\Presentation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresentationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'quantity' => (float) $this->quantity,
            'display' => $this->whenLoaded('presentationType', fn () => (float) $this->quantity.' '.$this->presentationType->abbreviation),
            'is_active' => $this->is_active,
            'presentation_type_id' => $this->presentationType?->uuid,
            'presentation_type' => $this->whenLoaded('presentationType', fn () => [
                'id' => $this->presentationType->uuid,
                'name' => $this->presentationType->name,
                'abbreviation' => $this->presentationType->abbreviation,
            ]),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
