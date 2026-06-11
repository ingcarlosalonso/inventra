<?php

namespace App\Models\ProductMovement\Scopes;

use App\Models\Product\Scopes\BySearch as ProductBySearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BySearch implements Scope
{
    public function __construct(private string $search) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('product', fn (Builder $q) => $q->withScopes(new ProductBySearch($this->search)));
    }
}
