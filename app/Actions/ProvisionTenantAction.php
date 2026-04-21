<?php

namespace App\Actions;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ProvisionTenantAction
{
    public function execute(array $data): Tenant
    {
        $subdomain = $data['subdomain'];
        $database = 'in_ventra_tenant_'.str_replace(['-', '.'], '_', $subdomain);
        $domain = env('SUBDOMAIN_URL', 'development.').$subdomain.env('EXT_SUBDOMAIN_URL', '.com');

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

        Tenant::forgetCurrent();

        return $tenant;
    }
}
