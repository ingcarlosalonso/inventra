<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureModuleEnabled;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureModuleEnabledTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        self::createModuleTables();
    }

    protected function tearDown(): void
    {
        app()->forgetInstance(config('multitenancy.current_tenant_container_key'));

        parent::tearDown();
    }

    public function test_allows_the_request_when_no_tenant_is_current(): void
    {
        $middleware = new EnsureModuleEnabled;

        $response = $middleware->handle(Request::create('/'), fn () => response('ok'), 'test_orders_quotes');

        $this->assertEquals('ok', $response->getContent());
    }

    public function test_allows_the_request_when_the_module_is_active_for_the_tenant(): void
    {
        $module = Module::factory()->create(['key' => 'test_orders_quotes']);
        $tenantModule = TenantModule::factory()->create(['module_id' => $module->id, 'status' => 'active']);
        $this->setCurrentTenant(Tenant::find($tenantModule->tenant_id));

        $middleware = new EnsureModuleEnabled;

        $response = $middleware->handle(Request::create('/'), fn () => response('ok'), 'test_orders_quotes');

        $this->assertEquals('ok', $response->getContent());
    }

    public function test_blocks_the_request_with_403_when_the_module_is_not_contracted(): void
    {
        Module::factory()->create(['key' => 'test_orders_quotes']);
        $tenant = TenantModule::factory()->create()->tenant;
        $this->setCurrentTenant($tenant);

        $middleware = new EnsureModuleEnabled;

        $response = $middleware->handle(Request::create('/'), fn () => response('ok'), 'test_orders_quotes');

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_blocks_the_request_with_json_message_when_json_is_expected(): void
    {
        Module::factory()->create(['key' => 'test_orders_quotes']);
        $tenant = TenantModule::factory()->create()->tenant;
        $this->setCurrentTenant($tenant);

        $middleware = new EnsureModuleEnabled;
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle($request, fn () => response('ok'), 'test_orders_quotes');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(__('central.module_not_enabled'), $response->getData(true)['message']);
    }

    private function setCurrentTenant(Tenant $tenant): void
    {
        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);
    }
}
