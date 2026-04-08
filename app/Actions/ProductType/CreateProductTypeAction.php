<?php

namespace App\Actions\ProductType;

use App\DTOs\ProductType\CreateProductTypeDTO;
use App\Models\ProductType;

class CreateProductTypeAction
{
    public function execute(CreateProductTypeDTO $dto): ProductType
    {
        return ProductType::create([
            'name' => $dto->name,
            'is_active' => $dto->isActive,
            'parent_id' => $dto->parentId,
        ]);
    }
}
