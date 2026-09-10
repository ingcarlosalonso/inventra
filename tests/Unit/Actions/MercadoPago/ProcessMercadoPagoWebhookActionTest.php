<?php

namespace Tests\Unit\Actions\MercadoPago;

use App\Actions\MercadoPago\ProcessMercadoPagoWebhookAction;
use App\Enums\MercadoPagoOrderStatus;
use App\Models\DailyCash;
use App\Models\MercadoPagoCredential;
use App\Models\MercadoPagoOrder;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Sale;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessMercadoPagoWebhookActionTest extends TestCase
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

    public function test_ignores_unknown_order(): void
    {
        app(ProcessMercadoPagoWebhookAction::class)->execute('does-not-exist');

        $this->assertTrue(true);
    }

    public function test_marks_order_as_failed_without_creating_payment(): void
    {
        MercadoPagoCredential::factory()->create(['access_token' => 'APP_USR-token']);
        $sale = Sale::factory()->create(['total' => 100]);
        $order = MercadoPagoOrder::factory()->create([
            'payable_type' => 'sale',
            'payable_id' => $sale->id,
            'mercado_pago_order_id' => 'ORDER1',
            'status' => MercadoPagoOrderStatus::Processing,
            'amount' => 100,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/orders/ORDER1' => Http::response([
                'id' => 'ORDER1',
                'status' => 'failed',
                'status_detail' => 'cc_rejected_insufficient_amount',
            ]),
        ]);

        app(ProcessMercadoPagoWebhookAction::class)->execute('ORDER1');

        $order->refresh();
        $this->assertSame(MercadoPagoOrderStatus::Failed, $order->status);
        $this->assertSame('cc_rejected_insufficient_amount', $order->failure_detail);
        $this->assertDatabaseCount('payments', 0, 'tenant');
    }

    public function test_registers_payment_when_order_is_processed(): void
    {
        MercadoPagoCredential::factory()->create(['access_token' => 'APP_USR-token']);
        $paymentMethod = PaymentMethod::factory()->create();
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => 'TERM123']);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);
        DailyCash::factory()->create(['point_of_sale_id' => $pos->id]);
        $order = MercadoPagoOrder::factory()->create([
            'payable_type' => 'sale',
            'payable_id' => $sale->id,
            'point_of_sale_id' => $pos->id,
            'payment_method_id' => $paymentMethod->id,
            'mercado_pago_order_id' => 'ORDER2',
            'status' => MercadoPagoOrderStatus::Processing,
            'amount' => 100,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/orders/ORDER2' => Http::response([
                'id' => 'ORDER2',
                'status' => 'processed',
                'transactions' => ['payments' => [['id' => 'PAY123']]],
            ]),
        ]);

        app(ProcessMercadoPagoWebhookAction::class)->execute('ORDER2');

        $order->refresh();
        $this->assertSame(MercadoPagoOrderStatus::Processed, $order->status);
        $this->assertSame('PAY123', $order->mercado_pago_payment_id);
        $this->assertNotNull($order->processed_at);
        $this->assertDatabaseHas('payments', [
            'payable_type' => 'sale',
            'payable_id' => $sale->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100,
        ], 'tenant');
    }

    public function test_does_not_duplicate_payment_on_repeated_webhook(): void
    {
        MercadoPagoCredential::factory()->create(['access_token' => 'APP_USR-token']);
        $paymentMethod = PaymentMethod::factory()->create();
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => 'TERM123']);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);
        DailyCash::factory()->create(['point_of_sale_id' => $pos->id]);
        MercadoPagoOrder::factory()->create([
            'payable_type' => 'sale',
            'payable_id' => $sale->id,
            'point_of_sale_id' => $pos->id,
            'payment_method_id' => $paymentMethod->id,
            'mercado_pago_order_id' => 'ORDER3',
            'status' => MercadoPagoOrderStatus::Processed,
            'mercado_pago_payment_id' => 'PAY123',
            'amount' => 100,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/orders/ORDER3' => Http::response([
                'id' => 'ORDER3',
                'status' => 'processed',
                'transactions' => ['payments' => [['id' => 'PAY123']]],
            ]),
        ]);

        app(ProcessMercadoPagoWebhookAction::class)->execute('ORDER3');

        $this->assertDatabaseCount('payments', 0, 'tenant');
    }

    public function test_ignores_unrecognized_status_without_touching_the_order(): void
    {
        MercadoPagoCredential::factory()->create(['access_token' => 'APP_USR-token']);
        $sale = Sale::factory()->create(['total' => 100]);
        $order = MercadoPagoOrder::factory()->create([
            'payable_type' => 'sale',
            'payable_id' => $sale->id,
            'mercado_pago_order_id' => 'ORDER4',
            'status' => MercadoPagoOrderStatus::Processing,
            'amount' => 100,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/orders/ORDER4' => Http::response([
                'id' => 'ORDER4',
                'status' => 'some_future_status',
            ]),
        ]);

        app(ProcessMercadoPagoWebhookAction::class)->execute('ORDER4');

        $order->refresh();
        $this->assertSame(MercadoPagoOrderStatus::Processing, $order->status);
        $this->assertDatabaseCount('payments', 0, 'tenant');
    }
}
