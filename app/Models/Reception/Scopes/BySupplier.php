<?php

namespace App\Models\Reception\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BySupplier implements Scope
{
    public function __construct(private int $supplierId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('supplier_id', $this->supplierId);
    }
}
