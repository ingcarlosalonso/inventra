<?php

namespace Tests\Feature\Controllers\Central;

use App\Models\Admin;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use Tests\TestCase;

class TenantModuleControllerTest extends TestCase
{
    private Admin $admin;

    private Tenant $tenant;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        self::createModuleTables();

        $this->admin = Admin::first() ?? Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin-test@inventra.com',
            'password' => bcrypt('password'),
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => uniqid('tenant-').'.test',
            'database' => 'in_ventra_tenant_'.uniqid(),
            'status' => 'active',
        ]);

        $this->module = Module::factory()->create(['key' => 'test_toggle_module']);
    }

    protected function tearDown(): void
    {
        TenantModule::query()->where('tenant_id', $this->tenant->id)->delete();
        $this->tenant->delete();
        $this->module->delete();

        parent::tearDown();
    }

    public function test_toggle_requires_auth(): void
    {
        $this->post("/tenants/{$this->tenant->id}/modules/{$this->module->id}/toggle")
            ->assertRedirect('/login');
    }

    public function test_toggle_enables_the_module_when_not_yet_contracted(): void
    {
        $this->actingAs($this->admin, 'central')
            ->post("/tenants/{$this->tenant->id}/modules/{$this->module->id}/toggle")
            ->assertRedirect();

        $this->assertTrue($this->tenant->fresh()->hasModule('test_toggle_module'));
    }

    public function test_toggle_suspends_an_active_module(): void
    {
        TenantModule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'module_id' => $this->module->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin, 'central')
            ->post("/tenants/{$this->tenant->id}/modules/{$this->module->id}/toggle")
            ->assertRedirect();

        $this->assertFalse($this->tenant->fresh()->hasModule('test_toggle_module'));
    }

    public function test_toggle_reactivates_a_suspended_module(): void
    {
        TenantModule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'module_id' => $this->module->id,
            'status' => 'suspended',
        ]);

        $this->actingAs($this->admin, 'central')
            ->post("/tenants/{$this->tenant->id}/modules/{$this->module->id}/toggle")
            ->assertRedirect();

        $this->assertTrue($this->tenant->fresh()->hasModule('test_toggle_module'));
    }
}
