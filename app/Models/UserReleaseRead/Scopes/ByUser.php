<?php

namespace App\Models\UserReleaseRead\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByUser implements Scope
{
    public function __construct(private int $userId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('user_id', $this->userId);
    }
}
