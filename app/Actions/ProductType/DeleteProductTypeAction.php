<?php

namespace App\Actions\ProductType;

use App\Exceptions\ProductTypeException;
use App\Models\ProductType;

class DeleteProductTypeAction
{
    public function execute(ProductType $productType): void
    {
        if ($productType->children()->exists()) {
            throw ProductTypeException::hasChildren();
        }

        $productType->delete();
    }
}
