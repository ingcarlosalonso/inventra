<?php

namespace App\Models\MercadoPagoOrder\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByMercadoPagoOrderId implements Scope
{
    public function __construct(private string $mercadoPagoOrderId) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('mercado_pago_order_id', $this->mercadoPagoOrderId);
    }
}
