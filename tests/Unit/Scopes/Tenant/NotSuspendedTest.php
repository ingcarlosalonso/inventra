<?php

namespace Tests\Unit\Scopes\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Scopes\NotSuspended;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotSuspendedTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    public function test_includes_active_tenants(): void
    {
        $tenant = Tenant::create($this->tenantData(['status' => 'active']));

        $results = Tenant::query()->withScopes(new NotSuspended)->get();

        $this->assertTrue($results->contains($tenant));
    }

    public function test_includes_trial_tenants(): void
    {
        $tenant = Tenant::create($this->tenantData(['status' => 'trial']));

        $results = Tenant::query()->withScopes(new NotSuspended)->get();

        $this->assertTrue($results->contains($tenant));
    }

    public function test_excludes_suspended_tenants(): void
    {
        $tenant = Tenant::create($this->tenantData(['status' => 'suspended']));

        $results = Tenant::query()->withScopes(new NotSuspended)->get();

        $this->assertFalse($results->contains($tenant));
    }

    private function tenantData(array $overrides = []): array
    {
        $slug = uniqid('t');

        return array_merge([
            'name' => 'Test Tenant',
            'domain' => "{$slug}.in-ventra.localhost",
            'database' => "in_ventra_tenant_{$slug}",
            'status' => 'active',
        ], $overrides);
    }
}
