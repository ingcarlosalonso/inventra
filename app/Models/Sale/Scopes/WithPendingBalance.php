<?php

namespace App\Models\Sale\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WithPendingBalance implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereRaw(
            'total > (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payable_type = "sale" AND payable_id = sales.id AND deleted_at IS NULL)'
        );
    }
}
