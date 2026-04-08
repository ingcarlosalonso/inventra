<?php

namespace App\Actions\CashMovementType;

use App\DTOs\CashMovementType\CreateCashMovementTypeDTO;
use App\Models\CashMovementType;

class CreateCashMovementTypeAction
{
    public function execute(CreateCashMovementTypeDTO $dto): CashMovementType
    {
        return CashMovementType::create([
            'name' => $dto->name,
            'is_income' => $dto->isIncome,
            'is_active' => $dto->isActive,
        ]);
    }
}
