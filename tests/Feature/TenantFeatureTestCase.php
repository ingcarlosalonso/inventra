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

        self::migrateTenantDb();

        $this->withoutMiddleware([NeedsTenant::class]);

        $this->user = User::factory()->create();
    }
}
