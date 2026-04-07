<?php

namespace App\Actions;

use Spatie\Multitenancy\Actions\MigrateTenantAction as BaseMigrateTenantAction;

class MigrateTenantAction extends BaseMigrateTenantAction
{
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            '--path'     => 'database/migrations/tenant',
            '--database' => config('multitenancy.tenant_database_connection_name'),
        ]);
    }
}
