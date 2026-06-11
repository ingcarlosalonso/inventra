<?php

namespace App\Models\CashMovementType\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BySearch implements Scope
{
    public function __construct(private string $search) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('name', 'like', "%{$this->search}%");
    }
}
