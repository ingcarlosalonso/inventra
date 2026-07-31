<?php

namespace App\Models\TenantModule\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ForModule implements Scope
{
    public function __construct(private int $moduleId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('module_id', $this->moduleId);
    }
}
