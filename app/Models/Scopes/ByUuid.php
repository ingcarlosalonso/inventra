<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByUuid implements Scope
{
    public function __construct(private string $uuid) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('uuid', $this->uuid);
    }
}
