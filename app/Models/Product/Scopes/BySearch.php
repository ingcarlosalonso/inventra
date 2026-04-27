<?php

namespace App\Models\Product\Scopes;

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
                ->orWhere('description', 'like', "%{$this->search}%")
                ->orWhereHas('barcodes', fn (Builder $b) => $b->where('barcode', 'like', "%{$this->search}%"));
        });
    }
}
