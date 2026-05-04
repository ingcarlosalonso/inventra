<?php

namespace App\Models\DailyCash\Scopes;

use App\Models\PointOfSale\Scopes\BySearch as PointOfSaleBySearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByPointOfSaleName implements Scope
{
    public function __construct(private string $name) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('pointOfSale', fn (Builder $q) => $q->withScopes(new PointOfSaleBySearch($this->name)));
    }
}
