<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByUuids implements Scope
{
    /** @param list<string> $uuids */
    public function __construct(private array $uuids) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereIn('uuid', $this->uuids);
    }
}
