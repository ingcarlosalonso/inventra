<?php

namespace Tests\Unit\Actions;

use App\Actions\CalculateDailyCashBalanceAction;
use App\Models\CashMovement;
use App\Models\CashMovementType;
use App\Models\DailyCash;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CalculateDailyCashBalanceActionTest extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$migrated) {
            Artisan::call('migrate:fresh', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
            self::$migrated = true;
        }
    }

    private function action(): CalculateDailyCashBalanceAction
    {
        return new CalculateDailyCashBalanceAction;
    }

    public function test_it_returns_opening_balance_when_no_movements(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 500.00]);

        $result = $this->action()->execute($dailyCash);

        $this->assertEquals(500.00, $result);
    }

    public function test_it_adds_sale_payments_to_balance(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 100.00]);
        $pm = PaymentMethod::factory()->create();

        Payment::factory()->create([
            'payable_type' => 'sale',
            'daily_cash_id' => $dailyCash->id,
            'payment_method_id' => $pm->id,
            'amount' => 200.00,
        ]);

        $result = $this->action()->execute($dailyCash);

        $this->assertEquals(300.00, $result);
    }

    public function test_it_adds_order_payments_to_balance(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 50.00]);
        $pm = PaymentMethod::factory()->create();

        Payment::factory()->create([
            'payable_type' => 'order',
            'daily_cash_id' => $dailyCash->id,
            'payment_method_id' => $pm->id,
            'amount' => 150.00,
        ]);

        $result = $this->action()->execute($dailyCash);

        $this->assertEquals(200.00, $result);
    }

    public function test_it_adds_income_cash_movements(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 100.00]);
        $incomeType = CashMovementType::factory()->create(['is_income' => true]);

        CashMovement::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'cash_movement_type_id' => $incomeType->id,
            'amount' => 300.00,
        ]);

        $result = $this->action()->execute($dailyCash);

        $this->assertEquals(400.00, $result);
    }

    public function test_it_subtracts_expense_cash_movements(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 500.00]);
        $expenseType = CashMovementType::factory()->expense()->create();

        CashMovement::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'cash_movement_type_id' => $expenseType->id,
            'amount' => 200.00,
        ]);

        $result = $this->action()->execute($dailyCash);

        $this->assertEquals(300.00, $result);
    }

    public function test_it_combines_all_sources_correctly(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 1000.00]);
        $pm = PaymentMethod::factory()->create();
        $incomeType = CashMovementType::factory()->create(['is_income' => true]);
        $expenseType = CashMovementType::factory()->expense()->create();

        // Payments: +500
        Payment::factory()->create([
            'payable_type' => 'sale',
            'daily_cash_id' => $dailyCash->id,
            'payment_method_id' => $pm->id,
            'amount' => 300.00,
        ]);
        Payment::factory()->create([
            'payable_type' => 'order',
            'daily_cash_id' => $dailyCash->id,
            'payment_method_id' => $pm->id,
            'amount' => 200.00,
        ]);

        // Income movements: +150
        CashMovement::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'cash_movement_type_id' => $incomeType->id,
            'amount' => 150.00,
        ]);

        // Expense movements: -100
        CashMovement::factory()->create([
            'daily_cash_id' => $dailyCash->id,
            'cash_movement_type_id' => $expenseType->id,
            'amount' => 100.00,
        ]);

        // Expected: 1000 + 500 + 150 - 100 = 1550
        $result = $this->action()->execute($dailyCash);

        $this->assertEquals(1550.00, $result);
    }

    public function test_it_ignores_payments_from_other_daily_cashes(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 100.00]);
        $otherDailyCash = DailyCash::factory()->create(['opening_balance' => 0.00]);
        $pm = PaymentMethod::factory()->create();

        // Payment linked to the other daily cash
        Payment::factory()->create([
            'payable_type' => 'sale',
            'daily_cash_id' => $otherDailyCash->id,
            'payment_method_id' => $pm->id,
            'amount' => 9999.00,
        ]);

        $result = $this->action()->execute($dailyCash);

        $this->assertEquals(100.00, $result);
    }

    public function test_it_rounds_to_two_decimal_places(): void
    {
        $dailyCash = DailyCash::factory()->create(['opening_balance' => 0.10]);
        $pm = PaymentMethod::factory()->create();

        Payment::factory()->create([
            'payable_type' => 'sale',
            'daily_cash_id' => $dailyCash->id,
            'payment_method_id' => $pm->id,
            'amount' => 0.20,
        ]);

        $result = $this->action()->execute($dailyCash);

        $this->assertEquals(0.30, $result);
        $this->assertIsFloat($result);

        // Verify it is properly rounded (not more than 2 decimal places)
        $this->assertEquals(round($result, 2), $result);
    }
}
