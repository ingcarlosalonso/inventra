<?php

namespace App\Models\PointOfSale\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WithAutoOpenTime implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotNull('auto_open_time');
    }
}
