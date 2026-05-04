<?php

namespace App\Models\Product\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByProductType implements Scope
{
    public function __construct(private int $productTypeId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('product_type_id', $this->productTypeId);
    }
}
