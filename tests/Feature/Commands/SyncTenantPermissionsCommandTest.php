<?php

namespace Tests\Feature\Commands;

use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncTenantPermissionsCommandTest extends TestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        self::migrateTenantDb();

        $this->tenant = Tenant::create([
            'name' => 'Sync Permissions Tenant',
            'domain' => uniqid('sync-permissions-').'.test',
            'database' => config('database.connections.tenant.database'),
            'status' => 'active',
        ]);

        $this->tenant->execute(function () {
            DB::connection('tenant')->table('permissions')->delete();
        });
    }

    protected function tearDown(): void
    {
        $this->tenant->delete();

        parent::tearDown();
    }

    public function test_seeds_missing_permissions_for_the_given_tenant(): void
    {
        $this->artisan('tenants:sync-permissions', ['--tenant' => [$this->tenant->id]])
            ->assertExitCode(0);

        $this->tenant->execute(function () {
            $this->assertTrue(Permission::where('name', 'manage_mercadopago')->exists());
        });
    }

    public function test_is_idempotent(): void
    {
        $this->artisan('tenants:sync-permissions', ['--tenant' => [$this->tenant->id]])->assertExitCode(0);
        $this->artisan('tenants:sync-permissions', ['--tenant' => [$this->tenant->id]])->assertExitCode(0);

        $this->tenant->execute(function () {
            $this->assertSame(1, Permission::where('name', 'manage_mercadopago')->count());
        });
    }

    public function test_warns_when_no_tenant_matches(): void
    {
        $this->artisan('tenants:sync-permissions', ['--tenant' => [-1]])
            ->expectsOutput('No tenants found.')
            ->assertExitCode(0);
    }
}
