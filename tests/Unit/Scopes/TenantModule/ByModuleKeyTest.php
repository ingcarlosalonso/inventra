<?php

namespace Tests\Unit\Scopes\TenantModule;

use App\Models\Module;
use App\Models\TenantModule;
use App\Models\TenantModule\Scopes\ByModuleKey;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ByModuleKeyTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        self::createModuleTables();
    }

    public function test_it_filters_tenant_modules_by_module_key(): void
    {
        $module = Module::factory()->create(['key' => 'test_by_module_key']);
        $tenantModule = TenantModule::factory()->create(['module_id' => $module->id]);
        $other = TenantModule::factory()->create();

        $results = TenantModule::query()->withScopes(new ByModuleKey('test_by_module_key'))->get();

        $this->assertTrue($results->contains($tenantModule));
        $this->assertFalse($results->contains($other));
    }
}
