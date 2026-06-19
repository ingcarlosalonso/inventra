<?php

namespace Tests\Unit\Scopes\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Scopes\Expired;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpiredTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    public function test_includes_tenants_whose_expiration_date_has_passed(): void
    {
        $tenant = Tenant::create($this->tenantData(['expires_at' => Carbon::yesterday()->toDateString()]));

        $results = Tenant::query()->withScopes(new Expired)->get();

        $this->assertTrue($results->contains($tenant));
    }

    public function test_excludes_tenants_expiring_today(): void
    {
        $tenant = Tenant::create($this->tenantData(['expires_at' => Carbon::today()->toDateString()]));

        $results = Tenant::query()->withScopes(new Expired)->get();

        $this->assertFalse($results->contains($tenant));
    }

    public function test_excludes_tenants_expiring_in_the_future(): void
    {
        $tenant = Tenant::create($this->tenantData(['expires_at' => Carbon::tomorrow()->toDateString()]));

        $results = Tenant::query()->withScopes(new Expired)->get();

        $this->assertFalse($results->contains($tenant));
    }

    public function test_excludes_tenants_with_no_expiration_date(): void
    {
        $tenant = Tenant::create($this->tenantData(['expires_at' => null]));

        $results = Tenant::query()->withScopes(new Expired)->get();

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
