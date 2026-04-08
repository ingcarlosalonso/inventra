<?php

namespace App\Actions\Supplier;

use App\DTOs\Supplier\UpdateSupplierDTO;
use App\Models\Supplier;

class UpdateSupplierAction
{
    public function execute(Supplier $supplier, UpdateSupplierDTO $dto): Supplier
    {
        $supplier->update([
            'name' => $dto->name,
            'contact_name' => $dto->contactName,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'notes' => $dto->notes,
            'is_active' => $dto->isActive,
        ]);

        return $supplier->fresh();
    }
}
