<?php

namespace App\Http\Controllers\Central;

use App\Actions\ProvisionTenantAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $tenants = Tenant::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'email' => $tenant->email,
                'contact_name' => $tenant->contact_name,
                'status' => $tenant->status,
                'plan' => $tenant->plan,
                'expires_at' => $tenant->expires_at?->format('Y-m-d'),
                'notes' => $tenant->notes,
                'created_at' => $tenant->created_at->format('Y-m-d'),
            ]);

        return Inertia::render('Central/Tenants/Index', compact('tenants'));
    }

    public function store(Request $request, ProvisionTenantAction $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9\-]+$/', 'unique:tenants,domain'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:trial,active,suspended'],
            'plan' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $action->execute($data);

        return back()->with('success', __('central.tenant_provisioned'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:trial,active,suspended'],
            'plan' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $tenant->update($data);

        return back()->with('success', __('central.tenant_updated'));
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        $tenant->suspend();

        return response()->json(['status' => $tenant->status]);
    }

    public function activate(Tenant $tenant): JsonResponse
    {
        $tenant->activate();

        return response()->json(['status' => $tenant->status]);
    }
}
