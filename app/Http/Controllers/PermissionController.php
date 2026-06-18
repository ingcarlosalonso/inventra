<?php

namespace App\Http\Controllers;

use App\Http\Resources\Role\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PermissionResource::collection(Permission::orderBy('name')->get());
    }
}
