<?php

namespace App\Actions\Client;

use App\Repositories\ClientRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListClientsAction
{
    public function __construct(private ClientRepository $repository) {}

    public function execute(?string $search = null): LengthAwarePaginator
    {
        return $this->repository->paginate($search);
    }
}
