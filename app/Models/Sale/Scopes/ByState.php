<?php

namespace App\Models\Sale\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByState implements Scope
{
    public function __construct(private int|string $saleStateId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('sale_state_id', $this->saleStateId);
    }
}
