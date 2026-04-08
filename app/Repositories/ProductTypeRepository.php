<?php

namespace App\Repositories;

use App\Models\ProductType;
use App\Models\ProductType\Scopes\BySearch;
use Illuminate\Database\Eloquent\Collection;

class ProductTypeRepository
{
    public function all(?string $search = null): Collection
    {
        $query = ProductType::withTrashed(false)->with('children');

        if ($search) {
            $query->withScopes(new BySearch($search));
        }

        return $query->orderBy('name')->get();
    }

    public function allFlat(?string $search = null): Collection
    {
        $query = ProductType::query();

        if ($search) {
            $query->withScopes(new BySearch($search));
        }

        return $query->orderBy('name')->get();
    }

    public function findByUuid(string $uuid): ProductType
    {
        return ProductType::where('uuid', $uuid)->firstOrFail();
    }

    public function rootOptions(): Collection
    {
        return ProductType::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
