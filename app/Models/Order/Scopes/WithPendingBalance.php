<?php

namespace App\Models\Order\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WithPendingBalance implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereRaw(
            'total > (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payable_type = "order" AND payable_id = orders.id AND deleted_at IS NULL)'
        );
    }
}
