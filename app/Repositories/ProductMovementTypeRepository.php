<?php

namespace App\Repositories;

use App\Models\ProductMovementType;
use App\Models\ProductMovementType\Scopes\BySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductMovementTypeRepository
{
    public function paginate(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = ProductMovementType::query();

        if ($search) {
            $query->withScopes(new BySearch($search));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findByUuid(string $uuid): ProductMovementType
    {
        return ProductMovementType::where('uuid', $uuid)->firstOrFail();
    }
}
