<?php

namespace Tests\Feature\Controllers;

use App\Enums\MercadoPagoOrderStatus;
use App\Models\MercadoPagoCredential;
use App\Models\MercadoPagoOrder;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Tests\Feature\TenantFeatureTestCase;

class MercadoPagoChargeControllerTest extends TenantFeatureTestCase
{
    public function test_store_creates_charge_for_sale(): void
    {
        MercadoPagoCredential::factory()->create(['payment_method_id' => PaymentMethod::factory(), 'access_token' => 'APP_USR-token']);
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => 'TERM123']);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);

        Http::fake([
            'https://api.mercadopago.com/v1/orders' => Http::response(['id' => 'ORDER1', 'status' => 'created']),
        ]);

        $this->actingAs($this->userWithPermissions('create_edit_delete_sales'), 'sanctum')
            ->postJson('/api/v1/sales/mercado-pago-charges', [
                'payable_type' => 'sale',
                'payable_id' => $sale->uuid,
                'amount' => 100,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'created');
    }

    public function test_store_requires_permission(): void
    {
        $sale = Sale::factory()->create(['total' => 100]);

        $this->actingAs($this->userWithoutPermissions(), 'sanctum')
            ->postJson('/api/v1/sales/mercado-pago-charges', [
                'payable_type' => 'sale',
                'payable_id' => $sale->uuid,
                'amount' => 100,
            ])
            ->assertForbidden();
    }

    public function test_store_validates_payable_exists(): void
    {
        $this->actingAs($this->userWithPermissions('create_edit_delete_sales'), 'sanctum')
            ->postJson('/api/v1/sales/mercado-pago-charges', [
                'payable_type' => 'sale',
                'payable_id' => 'not-a-real-uuid',
                'amount' => 100,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payable_id']);
    }

    public function test_show_polls_and_updates_status(): void
    {
        MercadoPagoCredential::factory()->create(['access_token' => 'APP_USR-token']);
        $paymentMethod = PaymentMethod::factory()->create();
        $pos = PointOfSale::factory()->create(['mercado_pago_terminal_id' => 'TERM123']);
        $sale = Sale::factory()->create(['total' => 100, 'point_of_sale_id' => $pos->id]);
        $order = MercadoPagoOrder::factory()->create([
            'payable_type' => 'sale',
            'payable_id' => $sale->id,
            'point_of_sale_id' => $pos->id,
            'payment_method_id' => $paymentMethod->id,
            'mercado_pago_order_id' => 'ORDER2',
            'status' => MercadoPagoOrderStatus::Processing,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/orders/ORDER2' => Http::response([
                'id' => 'ORDER2',
                'status' => 'processed',
                'transactions' => ['payments' => [['id' => 'PAY1']]],
            ]),
        ]);

        $this->actingAs($this->userWithPermissions('create_edit_delete_sales'), 'sanctum')
            ->getJson("/api/v1/sales/mercado-pago-charges/{$order->uuid}")
            ->assertOk()
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.is_successful', true);
    }
}
