<?php

namespace App\Actions\Client;

use App\DTOs\Client\UpdateClientDTO;
use App\Models\Client;

class UpdateClientAction
{
    public function execute(Client $client, UpdateClientDTO $dto): Client
    {
        $client->update([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'notes' => $dto->notes,
            'is_active' => $dto->isActive,
        ]);

        return $client->fresh();
    }
}
