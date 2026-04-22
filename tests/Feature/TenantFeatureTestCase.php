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

        // Set config and purge BEFORE DatabaseTransactions begins its transaction.
        // Since DatabaseTransactions uses a #[Before] hook in PHPUnit 11 that fires
        // after setUp(), we need to manually restart the transaction here.
        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'in_ventra_testing')]);
        DB::purge('tenant');
        DB::connection('tenant')->beginTransaction();

        self::migrateTenantDb();

        $this->withoutMiddleware([NeedsTenant::class]);

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
    }
}
