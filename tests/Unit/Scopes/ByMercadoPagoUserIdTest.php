<?php

namespace Tests\Unit\Scopes;

use App\Models\Tenant;
use App\Models\Tenant\Scopes\ByMercadoPagoUserId;
use Tests\TestCase;

class ByMercadoPagoUserIdTest extends TestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        self::createTenantsTable();

        $this->tenant = Tenant::create([
            'name' => 'Scope Test Tenant',
            'domain' => uniqid('scope-test-').'.test',
            'database' => 'in_ventra_tenant_'.uniqid(),
            'status' => 'active',
            'mercado_pago_user_id' => 'MPUSER123',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tenant->delete();

        parent::tearDown();
    }

    public function test_filters_by_mercado_pago_user_id(): void
    {
        $results = Tenant::query()->withScopes(new ByMercadoPagoUserId('MPUSER123'))->get();

        $this->assertCount(1, $results);
        $this->assertSame($this->tenant->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        $results = Tenant::query()->withScopes(new ByMercadoPagoUserId('does-not-exist'))->get();

        $this->assertCount(0, $results);
    }
}
