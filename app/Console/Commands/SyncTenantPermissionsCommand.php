<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;

class SyncTenantPermissionsCommand extends Command
{
    protected $signature = 'tenants:sync-permissions
                            {--tenant=* : Specific tenant IDs to sync}';

    protected $description = 'Run PermissionSeeder against existing tenants, adding any permission missing since their last provisioning';

    public function handle(): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::whereIn('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $tenant->execute(function () use ($tenant) {
                (new PermissionSeeder)->run();
                $this->line("Synced permissions for tenant: {$tenant->name} (id: {$tenant->id})");
            });
        }

        return self::SUCCESS;
    }
}
