<?php

namespace App\Actions\ProductMovementType;

use App\DTOs\ProductMovementType\UpdateProductMovementTypeDTO;
use App\Models\ProductMovementType;

class UpdateProductMovementTypeAction
{
    public function execute(ProductMovementType $type, UpdateProductMovementTypeDTO $dto): ProductMovementType
    {
        $type->update([
            'name' => $dto->name,
            'is_income' => $dto->isIncome,
            'is_active' => $dto->isActive,
        ]);

        return $type->fresh();
    }
}
