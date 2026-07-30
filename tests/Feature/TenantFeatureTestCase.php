<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Tests\TestCase;

abstract class TenantFeatureTestCase extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['tenant'];

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'in_ventra_testing')]);
        DB::purge('tenant');

        // Migrate before opening any transaction so Artisan can acquire the write lock.
        self::migrateTenantDb();

        // Set config and purge BEFORE DatabaseTransactions begins its transaction.
        // Since DatabaseTransactions uses a #[Before] hook in PHPUnit 11 that fires
        // after setUp(), we need to manually restart the transaction here.
        DB::connection('tenant')->beginTransaction();

        $this->withoutMiddleware([NeedsTenant::class]);

        (new PermissionSeeder)->run();

        $this->user = User::factory()->create();
        $this->user->syncPermissions(Permission::all());
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
    }

    protected function userWithoutPermissions(): User
    {
        return User::factory()->create();
    }

    protected function userWithPermissions(string|array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
