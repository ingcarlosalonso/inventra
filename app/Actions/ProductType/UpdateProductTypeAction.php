<?php

namespace App\Actions\ProductType;

use App\DTOs\ProductType\UpdateProductTypeDTO;
use App\Exceptions\ProductTypeException;
use App\Models\ProductType;

class UpdateProductTypeAction
{
    public function execute(ProductType $productType, UpdateProductTypeDTO $dto): ProductType
    {
        if ($dto->parentId !== null && $dto->parentId === $productType->id) {
            throw ProductTypeException::selfParent();
        }

        $productType->update([
            'name' => $dto->name,
            'is_active' => $dto->isActive,
            'parent_id' => $dto->parentId,
        ]);

        return $productType->fresh();
    }
}
