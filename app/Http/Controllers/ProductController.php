<?php

namespace App\Http\Controllers;

use App\Actions\StoreProductAction;
use App\Actions\UpdateProductAction;
use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Models\Product\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $query = Product::with([
            'productType',
            'barcodes',
            'currency',
            'productPresentations.presentation.presentationType',
        ]);

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return ProductResource::collection(
            $query->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreProductRequest $request, StoreProductAction $action): JsonResponse
    {
        return ProductResource::make($action->execute($request->validated()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): ProductResource
    {
        return ProductResource::make($action->execute($product, $request->validated()));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([], 204);
    }

    public function toggle(Product $product): ProductResource
    {
        $product->update(['is_active' => ! $product->is_active]);

        return ProductResource::make(
            $product->fresh()->load(['productType', 'barcodes', 'currency', 'productPresentations.presentation.presentationType'])
        );
    }
}
