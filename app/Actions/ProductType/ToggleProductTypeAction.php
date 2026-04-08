<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;

class ToggleProductTypeAction
{
    public function execute(ProductType $productType): ProductType
    {
        $productType->update(['is_active' => ! $productType->is_active]);

        return $productType->fresh();
    }
}
