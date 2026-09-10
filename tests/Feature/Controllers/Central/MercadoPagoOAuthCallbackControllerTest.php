<?php

namespace Tests\Feature\Controllers\Central;

use Tests\TestCase;

class MercadoPagoOAuthCallbackControllerTest extends TestCase
{
    public function test_rejects_missing_code(): void
    {
        $this->get('/oauth/mercadopago/callback?state=unknown-state')
            ->assertStatus(400);
    }

    public function test_rejects_unknown_or_expired_state(): void
    {
        $this->get('/oauth/mercadopago/callback?'.http_build_query([
            'code' => 'auth-code-123',
            'state' => 'unknown-state',
        ]))->assertStatus(400);
    }
}
