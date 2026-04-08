<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductMovementType\IndexProductMovementTypeRequest;
use App\Http\Requests\ProductMovementType\StoreProductMovementTypeRequest;
use App\Http\Requests\ProductMovementType\UpdateProductMovementTypeRequest;
use App\Http\Resources\ProductMovementType\ProductMovementTypeResource;
use App\Models\ProductMovementType;
use App\Models\ProductMovementType\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductMovementTypeController extends Controller
{
    public function index(IndexProductMovementTypeRequest $request): AnonymousResourceCollection
    {
        $query = ProductMovementType::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return ProductMovementTypeResource::collection(
            $query->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreProductMovementTypeRequest $request): ProductMovementTypeResource
    {
        return ProductMovementTypeResource::make(
            ProductMovementType::create($request->validated())
        );
    }

    public function update(UpdateProductMovementTypeRequest $request, ProductMovementType $productMovementType): ProductMovementTypeResource
    {
        $productMovementType->update($request->validated());

        return ProductMovementTypeResource::make($productMovementType->fresh());
    }

    public function destroy(ProductMovementType $productMovementType): JsonResponse
    {
        $productMovementType->delete();

        return response()->json([], 204);
    }
}
