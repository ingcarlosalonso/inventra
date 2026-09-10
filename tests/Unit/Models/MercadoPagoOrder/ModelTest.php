<?php

namespace Tests\Unit\Models\MercadoPagoOrder;

use App\Enums\MercadoPagoOrderStatus;
use App\Models\MercadoPagoOrder;
use App\Models\Model;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(MercadoPagoOrder::tableName(), [
            'id', 'uuid', 'payable_type', 'payable_id', 'point_of_sale_id', 'payment_method_id',
            'mercado_pago_order_id', 'external_reference', 'status', 'amount',
            'mercado_pago_payment_id', 'failure_detail', 'processed_at',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new MercadoPagoOrder);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $order = MercadoPagoOrder::factory()->create();

        $this->assertNotNull($order->uuid);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $order = MercadoPagoOrder::factory()->create(['status' => MercadoPagoOrderStatus::Processed]);

        $this->assertSame(MercadoPagoOrderStatus::Processed, $order->fresh()->status);
    }
}
