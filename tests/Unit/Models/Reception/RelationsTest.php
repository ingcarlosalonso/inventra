<?php

namespace Tests\Unit\Models\Reception;

use App\Models\DailyCash;
use App\Models\Reception;
use App\Models\ReceptionItem;
use App\Models\Supplier;
use App\Models\User;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $reception = Reception::factory()->create(['supplier_id' => $supplier->id]);

        $this->assertInstanceOf(Supplier::class, $reception->supplier);
    }

    public function test_supplier_is_nullable(): void
    {
        $reception = Reception::factory()->create(['supplier_id' => null]);

        $this->assertNull($reception->supplier);
    }

    public function test_belongs_to_daily_cash_nullable(): void
    {
        $reception = Reception::factory()->create(['daily_cash_id' => null]);

        $this->assertNull($reception->dailyCash);
    }

    public function test_belongs_to_daily_cash(): void
    {
        $dailyCash = DailyCash::factory()->create();
        $reception = Reception::factory()->create(['daily_cash_id' => $dailyCash->id]);

        $this->assertInstanceOf(DailyCash::class, $reception->dailyCash);
    }

    public function test_belongs_to_user(): void
    {
        $reception = Reception::factory()->create();

        $this->assertInstanceOf(User::class, $reception->user);
    }

    public function test_has_many_items(): void
    {
        $reception = Reception::factory()->create();
        ReceptionItem::factory()->count(2)->create(['reception_id' => $reception->id]);

        $this->assertCount(2, $reception->items);
    }
}
