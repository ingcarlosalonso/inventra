<?php

namespace App\Models\Release\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByVersion implements Scope
{
    public function __construct(private string $version) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('version', $this->version);
    }
}
