<?php

namespace App\Actions\Client;

use App\DTOs\Client\CreateClientDTO;
use App\Models\Client;

class CreateClientAction
{
    public function execute(CreateClientDTO $dto): Client
    {
        return Client::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'notes' => $dto->notes,
            'is_active' => $dto->isActive,
        ]);
    }
}
