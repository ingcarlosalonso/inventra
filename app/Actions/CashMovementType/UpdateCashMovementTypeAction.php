<?php

namespace App\Actions\CashMovementType;

use App\DTOs\CashMovementType\UpdateCashMovementTypeDTO;
use App\Models\CashMovementType;

class UpdateCashMovementTypeAction
{
    public function execute(CashMovementType $type, UpdateCashMovementTypeDTO $dto): CashMovementType
    {
        $type->update([
            'name' => $dto->name,
            'is_income' => $dto->isIncome,
            'is_active' => $dto->isActive,
        ]);

        return $type->fresh();
    }
}
