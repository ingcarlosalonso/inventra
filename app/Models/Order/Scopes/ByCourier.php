<?php

namespace App\Models\Order\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByCourier implements Scope
{
    public function __construct(private int $courierId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('courier_id', $this->courierId);
    }
}
