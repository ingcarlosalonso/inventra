<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\TenantModule\Scopes\ForModule;
use App\Models\TenantModule\Scopes\ForTenant;
use Illuminate\Http\RedirectResponse;

class TenantModuleController extends Controller
{
    public function toggle(Tenant $tenant, Module $module): RedirectResponse
    {
        $tenantModule = TenantModule::query()
            ->withScopes([new ForTenant($tenant->id), new ForModule($module->id)])
            ->first();

        if ($tenantModule && $tenantModule->isActive()) {
            $tenantModule->update(['status' => 'suspended']);
        } elseif ($tenantModule) {
            $tenantModule->update(['status' => 'active', 'expires_at' => null]);
        } else {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'status' => 'active',
            ]);
        }

        return back()->with('success', __('central.module_updated'));
    }
}
