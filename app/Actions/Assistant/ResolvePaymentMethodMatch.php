<?php

namespace App\Actions\Assistant;

use App\Models\PaymentMethod;
use App\Models\PaymentMethod\Scopes\BySearch;
use App\Models\Scopes\Active;
use Illuminate\Database\Eloquent\Collection;

class ResolvePaymentMethodMatch
{
    /**
     * Find active payment methods matching a free-text name (e.g. "efectivo", "tarjeta") as mentioned in chat.
     *
     * @return Collection<int, PaymentMethod>
     */
    public function execute(string $search): Collection
    {
        return PaymentMethod::query()
            ->withScopes([new Active, new BySearch($search)])
            ->orderBy('name')
            ->limit(5)
            ->get();
    }
}
