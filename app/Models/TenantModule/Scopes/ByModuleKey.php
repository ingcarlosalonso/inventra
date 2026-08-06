<?php

namespace App\Models\TenantModule\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByModuleKey implements Scope
{
    public function __construct(private string $key) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('module', function (Builder $query): void {
            $query->where('key', $this->key);
        });
    }
}
