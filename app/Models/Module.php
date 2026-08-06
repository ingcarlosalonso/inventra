<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    protected $guarded = [];

    public function getConnectionName(): string
    {
        return 'mysql';
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }
}
