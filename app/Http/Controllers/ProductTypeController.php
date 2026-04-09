<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductType\IndexProductTypeRequest;
use App\Http\Requests\ProductType\StoreProductTypeRequest;
use App\Http\Requests\ProductType\UpdateProductTypeRequest;
use App\Http\Resources\ProductType\ProductTypeResource;
use App\Models\ProductType;
use App\Models\ProductType\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductTypeController extends Controller
{
    public function index(IndexProductTypeRequest $request): AnonymousResourceCollection
    {
        $query = ProductType::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return ProductTypeResource::collection($query->orderBy('name')->get());
    }

    public function store(StoreProductTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['parent_id'] = $this->resolveParentId($data['parent_id'] ?? null);

        return ProductTypeResource::make(ProductType::create($data))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateProductTypeRequest $request, ProductType $productType): ProductTypeResource|JsonResponse
    {
        $data = $request->validated();

        if (($data['parent_id'] ?? null) === $productType->uuid) {
            return response()->json(['message' => __('product_types.self_parent_error')], 422);
        }

        $data['parent_id'] = $this->resolveParentId($data['parent_id'] ?? null);
        $productType->update($data);

        return ProductTypeResource::make($productType->fresh());
    }

    public function destroy(ProductType $productType): JsonResponse
    {
        if ($productType->children()->exists()) {
            return response()->json(['message' => __('product_types.has_children_error')], 422);
        }

        $productType->delete();

        return response()->json([], 204);
    }

    public function toggle(ProductType $productType): ProductTypeResource
    {
        $productType->update(['is_active' => ! $productType->is_active]);

        return ProductTypeResource::make($productType->fresh());
    }

    private function resolveParentId(?string $uuid): ?int
    {
        if (! $uuid) {
            return null;
        }

        return ProductType::where('uuid', $uuid)->value('id');
    }
}
