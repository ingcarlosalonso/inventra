<?php

namespace App\Http\Controllers;

use App\Http\Resources\Role\PermissionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PermissionResource::collection(Permission::orderBy('name')->get());
    }
}
