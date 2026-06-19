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
}
