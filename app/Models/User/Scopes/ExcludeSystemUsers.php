<?php

namespace App\Models\User\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ExcludeSystemUsers implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('is_system', false);
    }
}
