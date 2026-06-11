<?php

namespace App\Models\Sale\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByClient implements Scope
{
    public function __construct(private int $clientId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('client_id', $this->clientId);
    }
}
