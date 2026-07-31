<?php

namespace App\Models;

use App\Models\TenantModule\Scopes\ByModuleKey;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $guarded = [];

    public function getConnectionName(): string
    {
        return 'mysql';
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
        ];
    }

    public function isActive(): bool
    {
        if ($this->status === 'suspended') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function hasModule(string $key): bool
    {
        $tenantModule = $this->tenantModules()
            ->withScopes(new ByModuleKey($key))
            ->first();

        return $tenantModule?->isActive() ?? false;
    }
}
