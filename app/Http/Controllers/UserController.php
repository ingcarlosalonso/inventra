<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Models\User\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return UserResource::collection($query->orderBy('name')->paginate(20));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->except('roles'));

        if ($request->filled('roles')) {
            $user->syncRoles($request->input('roles'));
        }

        return UserResource::make($user->load('roles'))->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->safe()->except(['roles', 'password']);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->update($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->input('roles', []));
        }

        return UserResource::make($user->fresh()->load('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([], 204);
    }

    public function toggle(User $user): UserResource
    {
        $user->update(['is_active' => ! $user->is_active]);

        return UserResource::make($user->fresh()->load('roles'));
    }
}
