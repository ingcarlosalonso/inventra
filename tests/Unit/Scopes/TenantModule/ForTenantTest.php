<?php

namespace Tests\Unit\Scopes\TenantModule;

use App\Models\TenantModule;
use App\Models\TenantModule\Scopes\ForTenant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ForTenantTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        self::createModuleTables();
    }

    public function test_it_filters_tenant_modules_by_tenant(): void
    {
        $tenantModule = TenantModule::factory()->create();
        $other = TenantModule::factory()->create();

        $results = TenantModule::query()->withScopes(new ForTenant($tenantModule->tenant_id))->get();

        $this->assertTrue($results->contains($tenantModule));
        $this->assertFalse($results->contains($other));
    }
}
