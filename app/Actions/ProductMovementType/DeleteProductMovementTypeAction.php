<?php

namespace App\Actions\ProductMovementType;

use App\Models\ProductMovementType;

class DeleteProductMovementTypeAction
{
    public function execute(ProductMovementType $type): void
    {
        $type->delete();
    }
}
