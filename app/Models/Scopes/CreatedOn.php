<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Carbon;

class CreatedOn implements Scope
{
    public function __construct(private Carbon $date) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereDate('created_at', $this->date->toDateString());
    }
}
