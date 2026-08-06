<?php

namespace Tests\Unit\Models\Module;

use App\Models\Module;
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

    public function test_it_has_many_tenant_modules(): void
    {
        $module = Module::factory()->create();
        $tenantModule = TenantModule::factory()->create(['module_id' => $module->id]);

        $this->assertTrue($module->tenantModules->contains($tenantModule));
    }
}
