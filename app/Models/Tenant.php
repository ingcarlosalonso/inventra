<?php

namespace App\Models;

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
        return $this->status === 'active' || $this->status === 'trial';
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }
}
