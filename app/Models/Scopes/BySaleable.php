<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BySaleable implements Scope
{
    public function __construct(private string $saleableType, private int $saleableId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('saleable_type', $this->saleableType)
            ->where('saleable_id', $this->saleableId);
    }
}
