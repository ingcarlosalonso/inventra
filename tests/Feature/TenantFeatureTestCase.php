<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Tests\TestCase;

abstract class TenantFeatureTestCase extends TestCase
{
    private static bool $tenantMigrated = false;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'in_ventra_testing')]);
        DB::purge('tenant');

        if (! self::$tenantMigrated) {
            Artisan::call('migrate:fresh', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            self::$tenantMigrated = true;
        }

        $this->withoutMiddleware([NeedsTenant::class]);

        $this->user = User::factory()->create();
    }
}
