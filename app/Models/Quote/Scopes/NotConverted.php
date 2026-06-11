<?php

namespace App\Models\Quote\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NotConverted implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull('sale_id');
    }
}
