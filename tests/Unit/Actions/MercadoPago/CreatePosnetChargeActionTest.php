<?php

namespace Tests\Unit\Actions\MercadoPago;

use App\Actions\MercadoPago\CreatePosnetChargeAction;
use App\Enums\MercadoPagoOrderStatus;
use App\Exceptions\MercadoPagoException;
use App\Models\MercadoPagoCredential;
use App\Models\MercadoPagoOrder;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Sale;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreatePosnetChargeActionTest extends TestCase
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

    public function test_throws_when_tenant_has_no_credential(): void
    {
        $sale = Sale::factory()->create(['total' => 100]);

        $this->expectException(MercadoPagoException::class);

        app(CreatePosnetChargeAction::class)->execute('sale', $sale->uuid, 100);
    }

    public function test_throws_when_payment_method_not_configured(): void
    {
        MercadoPagoCredential::factory()->create(['payment_method_id' => null]);
        $sale = Sale::factory()->create(['total' => 100]);

        $this->expectExceptionMessage(__('mercadopago.error_payment_method_not_configured'));

        app(CreatePosnetChargeAction::class)->execute('sale', $sale->uuid, 100);
    }

    public function test_throws_when_point_of_sale_has_no_terminal(): void
    {
        MercadoPagoCredential::factory()->create(['payment_method_id' => PaymentMethod::factory()]);
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => null]);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);

        $this->expectExceptionMessage(__('mercadopago.error_terminal_not_configured'));

        app(CreatePosnetChargeAction::class)->execute('sale', $sale->uuid, 100);
    }

    public function test_throws_when_amount_exceeds_pending_balance(): void
    {
        MercadoPagoCredential::factory()->create(['payment_method_id' => PaymentMethod::factory()]);
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => 'TERM123']);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);

        $this->expectExceptionMessage(__('mercadopago.error_amount_exceeds_pending_balance'));

        app(CreatePosnetChargeAction::class)->execute('sale', $sale->uuid, 150);
    }

    public function test_creates_mercado_pago_order_for_pending_balance(): void
    {
        Http::fake([
            'https://api.mercadopago.com/v1/orders' => Http::response(['id' => 'ORDER1', 'status' => 'created']),
        ]);

        $paymentMethod = PaymentMethod::factory()->create();
        MercadoPagoCredential::factory()->create(['payment_method_id' => $paymentMethod->id, 'access_token' => 'APP_USR-token']);
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => 'TERM123']);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);
        Payment::factory()->create(['payable_type' => 'sale', 'payable_id' => $sale->id, 'amount' => 20]);

        $order = app(CreatePosnetChargeAction::class)->execute('sale', $sale->uuid, 80);

        $this->assertSame('ORDER1', $order->mercado_pago_order_id);
        $this->assertSame($pos->id, $order->point_of_sale_id);
        $this->assertSame($paymentMethod->id, $order->payment_method_id);
        $this->assertEquals(80, $order->amount);
        $this->assertDatabaseHas('mercado_pago_orders', ['mercado_pago_order_id' => 'ORDER1'], 'tenant');
    }

    public function test_subtracts_in_flight_mercado_pago_orders_from_pending_balance(): void
    {
        MercadoPagoCredential::factory()->create(['payment_method_id' => PaymentMethod::factory()]);
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => 'TERM123']);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);

        // An earlier charge for this same sale is still awaiting confirmation (no Payment
        // row exists for it yet) — it must still count against the remaining balance.
        MercadoPagoOrder::factory()->create([
            'payable_type' => 'sale',
            'payable_id' => $sale->id,
            'status' => MercadoPagoOrderStatus::AtTerminal,
            'amount' => 70,
        ]);

        $this->expectExceptionMessage(__('mercadopago.error_amount_exceeds_pending_balance'));

        app(CreatePosnetChargeAction::class)->execute('sale', $sale->uuid, 50);
    }

    public function test_defaults_to_created_status_when_response_status_is_unrecognized(): void
    {
        Http::fake([
            'https://api.mercadopago.com/v1/orders' => Http::response(['id' => 'ORDER9', 'status' => 'some_future_status']),
        ]);

        $paymentMethod = PaymentMethod::factory()->create();
        MercadoPagoCredential::factory()->create(['payment_method_id' => $paymentMethod->id, 'access_token' => 'APP_USR-token']);
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => 'TERM123']);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);

        $order = app(CreatePosnetChargeAction::class)->execute('sale', $sale->uuid, 50);

        $this->assertSame(MercadoPagoOrderStatus::Created, $order->status);
    }
}
