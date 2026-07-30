<?php

namespace App\Actions\Assistant;

use App\Models\PointOfSale;
use App\Models\PointOfSale\Scopes\BySearch;
use App\Models\Scopes\Active;
use Illuminate\Database\Eloquent\Collection;

class ResolvePointOfSaleMatch
{
    /**
     * Find active points of sale matching a free-text name, or all active ones when left blank
     * (the caller auto-selects when there is exactly one).
     *
     * @return Collection<int, PointOfSale>
     */
    public function execute(string $search): Collection
    {
        $query = PointOfSale::query()->withScopes(new Active);

        if ($search !== '') {
            $query->withScopes(new BySearch($search));
        }

        return $query->orderBy('name')->limit(5)->get();
    }
}
