<?php

namespace App\Http\Controllers;

use App\Actions\StoreCompositeProductAction;
use App\Actions\UpdateCompositeProductAction;
use App\Enums\SaleItemType;
use App\Http\Requests\CompositeProduct\IndexCompositeProductRequest;
use App\Http\Requests\CompositeProduct\StoreCompositeProductRequest;
use App\Http\Requests\CompositeProduct\UpdateCompositeProductRequest;
use App\Http\Resources\CompositeProduct\CompositeProductResource;
use App\Models\CompositeProduct;
use App\Models\CompositeProduct\Scopes\BySearch;
use App\Models\OrderItem;
use App\Models\QuoteItem;
use App\Models\SaleItem;
use App\Models\Scopes\BySaleable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompositeProductController extends Controller
{
    public function index(IndexCompositeProductRequest $request): AnonymousResourceCollection
    {
        $query = CompositeProduct::with('items.product');

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return CompositeProductResource::collection(
            $query->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreCompositeProductRequest $request, StoreCompositeProductAction $action): JsonResponse
    {
        return CompositeProductResource::make($action->execute($request->validated()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCompositeProductRequest $request, CompositeProduct $compositeProduct, UpdateCompositeProductAction $action): CompositeProductResource
    {
        return CompositeProductResource::make($action->execute($compositeProduct, $request->validated()));
    }

    public function destroy(CompositeProduct $compositeProduct): JsonResponse
    {
        $morphType = SaleItemType::Composite->morphType();
        $scope = new BySaleable($morphType, $compositeProduct->id);

        $isReferenced = SaleItem::query()->withScopes($scope)->exists()
            || OrderItem::query()->withScopes($scope)->exists()
            || QuoteItem::query()->withScopes($scope)->exists();

        if ($isReferenced) {
            return response()->json(['message' => __('composite_products.has_sales_error')], 422);
        }

        $compositeProduct->delete();

        return response()->json([], 204);
    }

    public function toggle(CompositeProduct $compositeProduct): CompositeProductResource
    {
        $compositeProduct->update(['is_active' => ! $compositeProduct->is_active]);

        return CompositeProductResource::make(
            $compositeProduct->fresh()->load('items.product')
        );
    }
}
