<?php

namespace App\Actions\ProductMovementType;

use App\DTOs\ProductMovementType\CreateProductMovementTypeDTO;
use App\Models\ProductMovementType;

class CreateProductMovementTypeAction
{
    public function execute(CreateProductMovementTypeDTO $dto): ProductMovementType
    {
        return ProductMovementType::create([
            'name' => $dto->name,
            'is_income' => $dto->isIncome,
            'is_active' => $dto->isActive,
        ]);
    }
}
