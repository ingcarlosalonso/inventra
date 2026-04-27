<?php

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions_count' => $this->when(isset($this->permissions_count), $this->permissions_count),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()),
        ];
    }
}
