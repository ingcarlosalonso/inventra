<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Scopes\Expired;
use App\Models\Tenant\Scopes\NotSuspended;
use Illuminate\Console\Command;

class SuspendExpiredTenantsCommand extends Command
{
    protected $signature = 'tenants:suspend-expired';

    protected $description = 'Suspend tenants whose expiration date has passed';

    public function handle(): int
    {
        $expired = Tenant::query()
            ->withScopes([new NotSuspended, new Expired])
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired tenants found.');

            return self::SUCCESS;
        }

        foreach ($expired as $tenant) {
            $tenant->suspend();
            $this->line("Suspended: {$tenant->name} (expired {$tenant->expires_at->toDateString()})");
        }

        $this->info("Suspended {$expired->count()} tenant(s).");

        return self::SUCCESS;
    }
}
