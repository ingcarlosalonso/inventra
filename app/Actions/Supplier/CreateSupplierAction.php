<?php

namespace App\Actions\Supplier;

use App\DTOs\Supplier\CreateSupplierDTO;
use App\Models\Supplier;

class CreateSupplierAction
{
    public function execute(CreateSupplierDTO $dto): Supplier
    {
        return Supplier::create([
            'name' => $dto->name,
            'contact_name' => $dto->contactName,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'notes' => $dto->notes,
            'is_active' => $dto->isActive,
        ]);
    }
}
