<?php

namespace App\Http\Resources\Release;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'version' => $this->version,
            'title' => $this->title,
            'summary' => $this->summary,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toISOString(),
            'items' => ReleaseItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
