<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\Client\Scopes\BySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientRepository
{
    public function paginate(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Client::query();

        if ($search) {
            $query->withScopes(new BySearch($search));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findByUuid(string $uuid): Client
    {
        return Client::where('uuid', $uuid)->firstOrFail();
    }
}
