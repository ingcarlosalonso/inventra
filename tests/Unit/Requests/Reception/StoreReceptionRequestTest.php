<?php

namespace Tests\Unit\Requests\Reception;

use App\Http\Requests\Reception\StoreReceptionRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreReceptionRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new StoreReceptionRequest)->rules();
    }

    public function test_received_at_is_required(): void
    {
        $v = Validator::make([
            'items' => [['product_presentation_id' => 'uuid', 'quantity' => 1, 'unit_cost' => 100]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('received_at', $v->errors()->toArray());
    }

    public function test_received_at_must_be_date(): void
    {
        $v = Validator::make([
            'received_at' => 'not-a-date',
            'items' => [['product_presentation_id' => 'uuid', 'quantity' => 1, 'unit_cost' => 100]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('received_at', $v->errors()->toArray());
    }

    public function test_items_is_required(): void
    {
        $v = Validator::make([
            'received_at' => '2026-04-13',
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('items', $v->errors()->toArray());
    }

    public function test_items_must_have_at_least_one(): void
    {
        $v = Validator::make([
            'received_at' => '2026-04-13',
            'items' => [],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('items', $v->errors()->toArray());
    }

    public function test_item_quantity_must_be_positive(): void
    {
        $v = Validator::make([
            'received_at' => '2026-04-13',
            'items' => [['product_presentation_id' => 'uuid', 'quantity' => 0, 'unit_cost' => 100]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('items.0.quantity', $v->errors()->toArray());
    }

    public function test_item_unit_cost_must_be_non_negative(): void
    {
        $v = Validator::make([
            'received_at' => '2026-04-13',
            'items' => [['product_presentation_id' => 'uuid', 'quantity' => 1, 'unit_cost' => -1]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('items.0.unit_cost', $v->errors()->toArray());
    }

    public function test_item_product_presentation_id_is_required(): void
    {
        $v = Validator::make([
            'received_at' => '2026-04-13',
            'items' => [['quantity' => 1, 'unit_cost' => 100]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('items.0.product_presentation_id', $v->errors()->toArray());
    }

    public function test_supplier_invoice_is_nullable(): void
    {
        $v = Validator::make([
            'received_at' => '2026-04-13',
            'supplier_invoice' => null,
            'items' => [['product_presentation_id' => 'uuid', 'quantity' => 1, 'unit_cost' => 100]],
        ], $this->rules());

        // Only existence rules would fail for uuid — structural rules pass
        $errors = $v->errors()->toArray();
        $this->assertArrayNotHasKey('supplier_invoice', $errors);
    }

    public function test_supplier_invoice_max_255(): void
    {
        $v = Validator::make([
            'received_at' => '2026-04-13',
            'supplier_invoice' => str_repeat('a', 256),
            'items' => [['product_presentation_id' => 'uuid', 'quantity' => 1, 'unit_cost' => 100]],
        ], $this->rules());

        $this->assertArrayHasKey('supplier_invoice', $v->errors()->toArray());
    }
}
