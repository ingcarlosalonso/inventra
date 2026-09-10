<?php

namespace Tests\Unit\Actions\MercadoPago;

use App\Actions\MercadoPago\ConnectMercadoPagoAccountAction;
use App\Models\MercadoPagoCredential;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConnectMercadoPagoAccountActionTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['tenant'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'in_ventra_testing')]);
        DB::purge('tenant');
        DB::connection('tenant')->beginTransaction();

        self::migrateTenantDb();
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
    }

    private function tokenResponse(): array
    {
        return [
            'access_token' => 'APP_USR-token',
            'refresh_token' => 'TG-token',
            'user_id' => 123456789,
            'public_key' => 'APP_USR-public',
            'live_mode' => true,
            'expires_in' => 15552000,
            'scope' => 'offline_access',
        ];
    }

    public function test_creates_credential_when_none_exists(): void
    {
        $credential = app(ConnectMercadoPagoAccountAction::class)->execute($this->tokenResponse());

        $this->assertSame('APP_USR-token', $credential->access_token);
        $this->assertSame('123456789', $credential->mercado_pago_user_id);
        $this->assertNotNull($credential->connected_at);
        $this->assertDatabaseCount('mercado_pago_credentials', 1, 'tenant');
    }

    public function test_overwrites_existing_credential_on_reconnect(): void
    {
        MercadoPagoCredential::factory()->create(['mercado_pago_user_id' => 'old-id']);

        app(ConnectMercadoPagoAccountAction::class)->execute($this->tokenResponse());

        $this->assertDatabaseCount('mercado_pago_credentials', 1, 'tenant');
        $this->assertDatabaseHas('mercado_pago_credentials', ['mercado_pago_user_id' => '123456789'], 'tenant');
    }

    public function test_does_not_fail_when_no_tenant_is_current(): void
    {
        $credential = app(ConnectMercadoPagoAccountAction::class)->execute($this->tokenResponse());

        $this->assertNotNull($credential);
    }
}
