<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Carbon;

class CreatedAfter implements Scope
{
    public function __construct(private Carbon $from) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('created_at', '>=', $this->from);
    }
}
