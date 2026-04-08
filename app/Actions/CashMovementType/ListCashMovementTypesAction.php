<?php

namespace App\Actions\CashMovementType;

use App\Repositories\CashMovementTypeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCashMovementTypesAction
{
    public function __construct(private CashMovementTypeRepository $repository) {}

    public function execute(?string $search = null): LengthAwarePaginator
    {
        return $this->repository->paginate($search);
    }
}
