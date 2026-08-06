<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantModule>
 */
class TenantModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::create([
                'name' => 'Test Tenant',
                'domain' => $this->faker->unique()->domainWord().'.test',
                'database' => 'in_ventra_tenant_'.$this->faker->unique()->lexify('??????'),
                'status' => 'active',
            ])->id,
            'module_id' => Module::factory(),
            'status' => 'active',
            'expires_at' => null,
            'notes' => null,
        ];
    }
}
