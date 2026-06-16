<?php

namespace App\Models\UserReleaseRead\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByReleaseUuid implements Scope
{
    public function __construct(private string $releaseUuid) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('release_uuid', $this->releaseUuid);
    }
}
