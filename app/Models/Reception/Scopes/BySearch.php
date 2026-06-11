<?php

namespace App\Models\Reception\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BySearch implements Scope
{
    public function __construct(private string $search) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where(function (Builder $q) {
            $q->where('supplier_invoice', 'like', "%{$this->search}%")
                ->orWhere('notes', 'like', "%{$this->search}%")
                ->orWhereHas('supplier', function (Builder $q) {
                    $q->where('name', 'like', "%{$this->search}%");
                });
        });
    }
}
