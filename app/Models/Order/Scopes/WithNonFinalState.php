<?php

namespace App\Models\Order\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WithNonFinalState implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('orderState', fn (Builder $q) => $q->where('is_final_state', false));
    }
}
