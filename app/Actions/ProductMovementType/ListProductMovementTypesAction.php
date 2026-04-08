<?php

namespace App\Actions\ProductMovementType;

use App\Repositories\ProductMovementTypeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListProductMovementTypesAction
{
    public function __construct(private ProductMovementTypeRepository $repository) {}

    public function execute(?string $search = null): LengthAwarePaginator
    {
        return $this->repository->paginate($search);
    }
}
