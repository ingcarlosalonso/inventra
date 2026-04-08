<?php

namespace App\Repositories;

use App\Models\CashMovementType;
use App\Models\CashMovementType\Scopes\BySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CashMovementTypeRepository
{
    public function paginate(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = CashMovementType::query();

        if ($search) {
            $query->withScopes(new BySearch($search));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findByUuid(string $uuid): CashMovementType
    {
        return CashMovementType::where('uuid', $uuid)->firstOrFail();
    }
}
