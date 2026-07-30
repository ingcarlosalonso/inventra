<?php

namespace App\Models\Tenant\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Carbon;

class Expired implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotNull('expires_at')->where('expires_at', '<', Carbon::today());
    }
}
