<?php

namespace App\Actions\Supplier;

use App\Repositories\SupplierRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSuppliersAction
{
    public function __construct(private SupplierRepository $repository) {}

    public function execute(?string $search = null): LengthAwarePaginator
    {
        return $this->repository->paginate($search);
    }
}
