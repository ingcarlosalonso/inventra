<?php

namespace App\Actions;

use App\Models\CashMovement;
use App\Models\DailyCash;
use App\Models\Payment;
use App\Models\Reception;

class CalculateDailyCashBalanceAction
{
    public function execute(DailyCash $dailyCash): float
    {
        $paymentsSum = Payment::where('daily_cash_id', $dailyCash->id)->sum('amount');

        $incomeSql = CashMovement::where('daily_cash_id', $dailyCash->id)
            ->whereRelation('cashMovementType', 'is_income', true)
            ->sum('amount');

        $expenseSql = CashMovement::where('daily_cash_id', $dailyCash->id)
            ->whereRelation('cashMovementType', 'is_income', false)
            ->sum('amount');

        $receptionsSum = Reception::where('daily_cash_id', $dailyCash->id)->sum('total');

        return round(
            (float) $dailyCash->opening_balance
            + (float) $paymentsSum
            + (float) $incomeSql
            - (float) $expenseSql
            - (float) $receptionsSum,
            2
        );
    }
}
