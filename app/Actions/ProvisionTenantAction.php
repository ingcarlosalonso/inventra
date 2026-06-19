<?php

namespace App\Actions;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProvisionTenantAction
{
    public function __construct(private CreateTenantDefaultUsersAction $createDefaultUsers) {}

    public function execute(array $data): Tenant
    {
        $subdomain = $data['subdomain'];

        if (! preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            throw new InvalidArgumentException("Invalid subdomain: {$subdomain}");
        }

        $database = 'in_ventra_tenant_'.str_replace('-', '_', $subdomain);
        $domain = config('app.subdomain_prefix').$subdomain.config('app.subdomain_suffix');

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tenant = Tenant::create([
            'name' => $data['name'],
            'domain' => $domain,
            'database' => $database,
            'email' => $data['email'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'status' => $data['status'] ?? 'trial',
            'plan' => $data['plan'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $tenant->makeCurrent();

        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'tenant',
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--database' => 'tenant',
            '--force' => true,
        ]);

        $this->createDefaultUsers->execute($data['contact_name'] ?? null);

        Tenant::forgetCurrent();

        return $tenant;
    }
}
