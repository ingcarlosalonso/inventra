<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    private static bool $tenantDbMigrated = false;

    protected static function migrateTenantDb(): void
    {
        if (self::$tenantDbMigrated) {
            return;
        }

        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        self::$tenantDbMigrated = true;
    }
}
