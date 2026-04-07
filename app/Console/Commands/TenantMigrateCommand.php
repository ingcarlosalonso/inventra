<?php

namespace App\Console\Commands;

use App\Actions\MigrateTenantAction;
use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Run seeders after migrating}
                            {--tenant=* : Specific tenant IDs to migrate}';

    protected $description = 'Run migrations on tenant databases';

    public function handle(): void
    {
        $tenants = $this->option('tenant')
            ? Tenant::whereIn('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return;
        }

        /** @var MigrateTenantAction $action */
        $action = app(MigrateTenantAction::class);

        if ($this->option('fresh')) {
            $action->fresh();
        }

        if ($this->option('seed')) {
            $action->seed();
        }

        foreach ($tenants as $tenant) {
            $this->info("Running migrations for tenant: {$tenant->name} (id: {$tenant->id})");
            $this->line(str_repeat('-', 57));

            $action->output($this->output)->execute($tenant);

            $this->newLine();
        }
    }
}
