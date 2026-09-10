<?php

namespace App\Jobs;

use App\Actions\MercadoPago\ProcessMercadoPagoWebhookAction;
use App\Models\Tenant;
use App\Models\Tenant\Scopes\ByMercadoPagoUserId;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class ProcessMercadoPagoWebhookJob implements NotTenantAware, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $mercadoPagoUserId,
        public readonly string $mercadoPagoOrderId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::query()->withScopes(new ByMercadoPagoUserId($this->mercadoPagoUserId))->first();

        if (! $tenant) {
            Log::warning("Mercado Pago webhook received for unknown account [{$this->mercadoPagoUserId}].");

            return;
        }

        if (! $tenant->hasModule('mercado_pago')) {
            Log::warning("Mercado Pago webhook received for tenant [{$tenant->id}] but the Mercado Pago module is not enabled.");

            return;
        }

        $tenant->execute(function () {
            app(ProcessMercadoPagoWebhookAction::class)->execute($this->mercadoPagoOrderId);
        });
    }
}
