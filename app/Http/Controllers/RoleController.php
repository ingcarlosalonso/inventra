<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\Role\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection(
            Role::withCount('permissions')->orderBy('name')->get()
        );
    }

    public function show(Role $role): RoleResource
    {
        return RoleResource::make($role->load('permissions'));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->input('name'), 'guard_name' => 'web']);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->input('permissions'));
        }

        return RoleResource::make($role->load('permissions'))->response()->setStatusCode(201);
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role->update(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permissions', []));

        return RoleResource::make($role->fresh()->load('permissions'));
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json([], 204);
    }
}
