<?php

namespace App\Models\TenantModule\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ForTenant implements Scope
{
    public function __construct(private int $tenantId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('tenant_id', $this->tenantId);
    }
}
