<?php

namespace App\Actions\Assistant;

use App\Models\Client;
use App\Models\Client\Scopes\BySearch;
use App\Models\Scopes\Active;
use Illuminate\Database\Eloquent\Collection;

class ResolveClientMatch
{
    /**
     * Find active clients matching a free-text name/phone/email as mentioned in chat.
     *
     * @return Collection<int, Client>
     */
    public function execute(string $search): Collection
    {
        return Client::query()
            ->withScopes([new Active, new BySearch($search)])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(5)
            ->get();
    }
}
