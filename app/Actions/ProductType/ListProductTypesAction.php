<?php

namespace App\Actions\ProductType;

use App\Repositories\ProductTypeRepository;
use Illuminate\Database\Eloquent\Collection;

class ListProductTypesAction
{
    public function __construct(private ProductTypeRepository $repository) {}

    public function execute(?string $search = null): Collection
    {
        return $this->repository->allFlat($search);
    }
}
