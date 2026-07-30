<?php

namespace Tests\Unit\Actions\Assistant;

use App\Actions\Assistant\ResolvePaymentMethodMatch;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResolvePaymentMethodMatchTest extends TestCase
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

    public function test_finds_active_payment_method_by_partial_name(): void
    {
        $method = PaymentMethod::factory()->create(['name' => 'Efectivo']);

        $result = (new ResolvePaymentMethodMatch)->execute('efec');

        $this->assertCount(1, $result);
        $this->assertSame($method->id, $result->first()->id);
    }

    public function test_excludes_inactive_payment_methods(): void
    {
        PaymentMethod::factory()->create(['name' => 'Tarjeta Vieja', 'is_active' => false]);

        $result = (new ResolvePaymentMethodMatch)->execute('Tarjeta Vieja');

        $this->assertCount(0, $result);
    }

    public function test_returns_empty_when_no_match(): void
    {
        $result = (new ResolvePaymentMethodMatch)->execute('zzz_nonexistent_zzz');

        $this->assertCount(0, $result);
    }
}
