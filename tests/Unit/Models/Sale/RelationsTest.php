<?php

namespace Tests\Unit\Models\Sale;

use App\Models\Payment;
use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleState;
use App\Models\User;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_client_nullable(): void
    {
        $sale = Sale::factory()->create(['client_id' => null]);

        $this->assertNull($sale->client);
    }

    public function test_belongs_to_point_of_sale(): void
    {
        $pos = PointOfSale::factory()->create();
        $sale = Sale::factory()->create(['point_of_sale_id' => $pos->id]);

        $this->assertInstanceOf(PointOfSale::class, $sale->pointOfSale);
    }

    public function test_belongs_to_sale_state(): void
    {
        $state = SaleState::factory()->create();
        $sale = Sale::factory()->create(['sale_state_id' => $state->id]);

        $this->assertInstanceOf(SaleState::class, $sale->saleState);
    }

    public function test_belongs_to_currency_nullable(): void
    {
        $sale = Sale::factory()->create(['currency_id' => null]);

        $this->assertNull($sale->currency);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $sale->user);
    }

    public function test_has_many_items(): void
    {
        $sale = Sale::factory()->create();
        SaleItem::factory()->count(2)->create(['sale_id' => $sale->id]);

        $this->assertCount(2, $sale->items);
        $this->assertInstanceOf(SaleItem::class, $sale->items->first());
    }

    public function test_morph_many_payments(): void
    {
        $sale = Sale::factory()->create();
        Payment::factory()->count(2)->create(['payable_type' => 'sale', 'payable_id' => $sale->id]);

        $this->assertCount(2, $sale->payments);
        $this->assertInstanceOf(Payment::class, $sale->payments->first());
    }
}
