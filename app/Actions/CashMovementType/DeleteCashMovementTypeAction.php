<?php

namespace App\Actions\CashMovementType;

use App\Models\CashMovementType;

class DeleteCashMovementTypeAction
{
    public function execute(CashMovementType $type): void
    {
        $type->delete();
    }
}
