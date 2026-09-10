<?php

namespace App\Models\Tenant\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByMercadoPagoUserId implements Scope
{
    public function __construct(private string $mercadoPagoUserId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('mercado_pago_user_id', $this->mercadoPagoUserId);
    }
}
