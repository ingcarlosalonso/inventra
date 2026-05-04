<?php

namespace App\Models\DailyCash\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Carbon;

class ByDateRange implements Scope
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereBetween('opened_at', [$this->from->startOfDay(), $this->to->endOfDay()]);
    }
}
