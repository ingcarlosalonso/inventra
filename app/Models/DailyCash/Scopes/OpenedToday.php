<?php

namespace App\Models\DailyCash\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OpenedToday implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereDate('opened_at', today());
    }
}
