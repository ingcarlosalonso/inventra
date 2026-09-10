<?php

namespace Tests\Unit\Models\MercadoPagoCredential;

use App\Models\MercadoPagoCredential;
use App\Models\Model;
use Illuminate\Support\Facades\DB;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(MercadoPagoCredential::tableName(), [
            'id', 'access_token', 'refresh_token', 'mercado_pago_user_id',
            'public_key', 'scope', 'live_mode', 'token_expires_at', 'connected_at', 'payment_method_id',
            'created_at', 'updated_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new MercadoPagoCredential);
    }

    public function test_access_token_is_encrypted_at_rest(): void
    {
        $credential = MercadoPagoCredential::factory()->create(['access_token' => 'APP_USR-plain-token']);

        $raw = DB::connection('tenant')
            ->table('mercado_pago_credentials')
            ->where('id', $credential->id)
            ->value('access_token');

        $this->assertNotEquals('APP_USR-plain-token', $raw);
        $this->assertEquals('APP_USR-plain-token', $credential->fresh()->access_token);
    }
}
