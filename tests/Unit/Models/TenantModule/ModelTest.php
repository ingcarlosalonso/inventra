<?php

namespace Tests\Unit\Models\TenantModule;

use App\Models\TenantModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        self::createModuleTables();
    }

    public function test_it_has_expected_columns(): void
    {
        $expected = ['id', 'tenant_id', 'module_id', 'status', 'expires_at', 'notes', 'created_at', 'updated_at'];
        $actual = Schema::getColumnListing('tenant_modules');

        sort($expected);
        sort($actual);

        $this->assertEquals($expected, $actual);
    }

    public function test_it_extends_from_eloquent_model(): void
    {
        $tenantModule = TenantModule::factory()->create();

        $this->assertInstanceOf(TenantModule::class, $tenantModule);
    }

    public function test_it_uses_the_mysql_connection(): void
    {
        $tenantModule = new TenantModule;

        $this->assertEquals('mysql', $tenantModule->getConnectionName());
    }

    public function test_is_active_returns_false_when_suspended(): void
    {
        $tenantModule = TenantModule::factory()->create(['status' => 'suspended']);

        $this->assertFalse($tenantModule->isActive());
    }

    public function test_is_active_returns_false_when_expired(): void
    {
        $tenantModule = TenantModule::factory()->create([
            'status' => 'active',
            'expires_at' => Carbon::yesterday(),
        ]);

        $this->assertFalse($tenantModule->isActive());
    }

    public function test_is_active_returns_true_when_active_and_not_expired(): void
    {
        $tenantModule = TenantModule::factory()->create([
            'status' => 'active',
            'expires_at' => Carbon::tomorrow(),
        ]);

        $this->assertTrue($tenantModule->isActive());
    }

    public function test_is_active_returns_true_when_active_with_no_expiration(): void
    {
        $tenantModule = TenantModule::factory()->create([
            'status' => 'active',
            'expires_at' => null,
        ]);

        $this->assertTrue($tenantModule->isActive());
    }
}
