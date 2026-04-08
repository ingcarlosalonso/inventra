<?php

namespace App\Repositories;

use App\Models\Supplier;
use App\Models\Supplier\Scopes\BySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierRepository
{
    public function paginate(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Supplier::query();

        if ($search) {
            $query->withScopes(new BySearch($search));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findByUuid(string $uuid): Supplier
    {
        return Supplier::where('uuid', $uuid)->firstOrFail();
    }
}
