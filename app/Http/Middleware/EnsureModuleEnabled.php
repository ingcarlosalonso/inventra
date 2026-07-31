<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $tenant = Tenant::current();

        if ($tenant) {
            foreach ($modules as $module) {
                if (! $tenant->hasModule($module)) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => __('central.module_not_enabled')], 403);
                    }

                    return response()->view('errors.module-not-enabled', [], 403);
                }
            }
        }

        return $next($request);
    }
}
