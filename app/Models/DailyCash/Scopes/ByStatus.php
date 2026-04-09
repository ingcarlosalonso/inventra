<?php

namespace App\Models\DailyCash\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByStatus implements Scope
{
    public function __construct(private bool $isClosed) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('is_closed', $this->isClosed);
    }
}
