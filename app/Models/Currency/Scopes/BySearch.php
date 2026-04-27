<?php

namespace App\Models\Currency\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BySearch implements Scope
{
    public function __construct(private string $search) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where(function (Builder $q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('symbol', 'like', "%{$this->search}%")
                ->orWhere('iso_code', 'like', "%{$this->search}%");
        });
    }
}
