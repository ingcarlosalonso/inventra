<?php

namespace App\Models\Tenant\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NotSuspended implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereIn('status', ['active', 'trial']);
    }
}
