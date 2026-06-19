<?php

namespace App\Models\ProductPresentation\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByBarcode implements Scope
{
    public function __construct(private string $barcode) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('barcodes', fn (Builder $q) => $q->where('barcode', $this->barcode));
    }
}
