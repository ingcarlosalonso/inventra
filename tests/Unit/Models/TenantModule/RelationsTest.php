<?php

namespace Tests\Unit\Models\TenantModule;

use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RelationsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        self::createModuleTables();
    }

    public function test_it_belongs_to_a_tenant(): void
    {
        $tenantModule = TenantModule::factory()->create();

        $this->assertInstanceOf(Tenant::class, $tenantModule->tenant);
    }

    public function test_it_belongs_to_a_module(): void
    {
        $tenantModule = TenantModule::factory()->create();

        $this->assertInstanceOf(Module::class, $tenantModule->module);
    }
}
