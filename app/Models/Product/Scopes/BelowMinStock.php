<?php

namespace App\Models\Product\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BelowMinStock implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereRaw('stock <= min_stock');
    }
}
