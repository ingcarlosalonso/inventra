<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if ($tenant && ! $tenant->isActive()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('central.tenant_suspended_access')], 403);
            }

            return response()->view('errors.tenant-suspended', [], 403);
        }

        return $next($request);
    }
}
