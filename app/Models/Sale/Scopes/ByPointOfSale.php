<?php

namespace App\Models\Sale\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByPointOfSale implements Scope
{
    public function __construct(private int $pointOfSaleId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('point_of_sale_id', $this->pointOfSaleId);
    }
}
