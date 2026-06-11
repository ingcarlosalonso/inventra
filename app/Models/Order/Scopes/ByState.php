<?php

namespace App\Models\Order\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByState implements Scope
{
    public function __construct(private int|string $orderStateId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('order_state_id', $this->orderStateId);
    }
}
