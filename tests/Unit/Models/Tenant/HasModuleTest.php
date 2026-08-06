<?php

namespace Tests\Unit\Models\Tenant;

use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HasModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        self::createModuleTables();
    }

    public function test_returns_true_when_module_is_active_for_the_tenant(): void
    {
        $module = Module::factory()->create(['key' => 'test_ai_assistant']);
        $tenantModule = TenantModule::factory()->create(['module_id' => $module->id, 'status' => 'active']);

        $tenant = Tenant::find($tenantModule->tenant_id);

        $this->assertTrue($tenant->hasModule('test_ai_assistant'));
    }

    public function test_returns_false_when_module_is_suspended_for_the_tenant(): void
    {
        $module = Module::factory()->create(['key' => 'test_ai_assistant']);
        $tenantModule = TenantModule::factory()->create(['module_id' => $module->id, 'status' => 'suspended']);

        $tenant = Tenant::find($tenantModule->tenant_id);

        $this->assertFalse($tenant->hasModule('test_ai_assistant'));
    }

    public function test_returns_false_when_tenant_never_contracted_the_module(): void
    {
        Module::factory()->create(['key' => 'test_ai_assistant']);
        $tenantModule = TenantModule::factory()->create();

        $tenant = Tenant::find($tenantModule->tenant_id);

        $this->assertFalse($tenant->hasModule('test_ai_assistant'));
    }
}
