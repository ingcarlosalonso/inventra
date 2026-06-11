<?php

namespace Tests\Feature;

use App\Models\User;
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

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
    }
}
