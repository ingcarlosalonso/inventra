<?php

namespace Tests\Feature\Controllers;

use Tests\Feature\TenantFeatureTestCase;

class ApiV1Test extends TenantFeatureTestCase
{
    // ── Auth ──────────────────────────────────────────────────────────────────

    public function test_login_endpoint_exists_and_validates(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable();
    }

    public function test_login_with_invalid_credentials_returns_unauthorized(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'wrong@email.com', 'password' => 'wrong'])
            ->assertUnauthorized();
    }

    public function test_protected_routes_require_authentication(): void
    {
        $endpoints = [
            ['GET', '/api/v1/cash-movement-types'],
            ['GET', '/api/v1/clients'],
            ['GET', '/api/v1/daily-cashes'],
            ['GET', '/api/v1/dashboard'],
            ['GET', '/api/v1/notifications'],
            ['GET', '/api/v1/orders'],
            ['GET', '/api/v1/orders/couriers'],
            ['GET', '/api/v1/orders/states'],
            ['GET', '/api/v1/products'],
            ['GET', '/api/v1/products/composite'],
            ['GET', '/api/v1/products/movement-types'],
            ['GET', '/api/v1/products/movements'],
            ['GET', '/api/v1/products/presentation-types'],
            ['GET', '/api/v1/products/presentations'],
            ['GET', '/api/v1/products/promotions'],
            ['GET', '/api/v1/products/types'],
            ['GET', '/api/v1/quotes'],
            ['GET', '/api/v1/receptions'],
            ['GET', '/api/v1/reports/clients'],
            ['GET', '/api/v1/reports/daily-cashes'],
            ['GET', '/api/v1/reports/inventory'],
            ['GET', '/api/v1/reports/orders'],
            ['GET', '/api/v1/reports/payments'],
            ['GET', '/api/v1/reports/products'],
            ['GET', '/api/v1/reports/sales'],
            ['GET', '/api/v1/sales'],
            ['GET', '/api/v1/sales/payment-methods'],
            ['GET', '/api/v1/sales/payments/pending'],
            ['GET', '/api/v1/sales/points-of-sale'],
            ['GET', '/api/v1/sales/states'],
            ['GET', '/api/v1/settings/currencies'],
            ['GET', '/api/v1/settings/customization'],
            ['GET', '/api/v1/suppliers'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $this->assertContains(
                $response->status(),
                [401, 403],
                "Expected 401/403 for {$method} {$url}, got {$response->status()}"
            );
        }
    }

    public function test_authenticated_dashboard_returns_ok(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    }

    public function test_unknown_v1_route_returns_not_found(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/non-existent-resource')
            ->assertNotFound();
    }
}
