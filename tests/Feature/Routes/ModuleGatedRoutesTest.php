<?php

namespace Tests\Feature\Routes;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ModuleGatedRoutesTest extends TestCase
{
    /**
     * Every route below must require the given module, on both the Inertia
     * page route (web.php) and its backing API route (v1.php). A route
     * missing this middleware lets a tenant reach it directly by URL even
     * when the module isn't contracted.
     */
    private function gatedRouteNames(): array
    {
        return [
            'quotes' => 'orders_quotes',
            'quotes.create' => 'orders_quotes',
            'quotes.page-show' => 'orders_quotes',
            'orders' => 'orders_quotes',
            'orders.create' => 'orders_quotes',
            'orders.page-show' => 'orders_quotes',
            'settings.order-states' => 'orders_quotes',
            'settings.couriers' => 'orders_quotes',
            'reports.orders' => 'orders_quotes',
        ];
    }

    public function test_web_page_routes_require_their_module(): void
    {
        foreach ($this->gatedRouteNames() as $name => $module) {
            $route = collect(Route::getRoutes())->first(fn ($r) => $r->getName() === $name);

            $this->assertNotNull($route, "Route [{$name}] does not exist.");
            $this->assertContains(
                "module:{$module}",
                $route->gatherMiddleware(),
                "Route [{$name}] is missing the [module:{$module}] middleware."
            );
        }
    }

    public function test_v1_assistant_chat_requires_its_module(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($r) => $r->uri() === 'api/v1/assistant/chat' && in_array('POST', $r->methods())
        );

        $this->assertNotNull($route);
        $this->assertContains('module:ai_assistant', $route->gatherMiddleware());
    }
}
