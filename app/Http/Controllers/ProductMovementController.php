<?php

namespace App\Http\Controllers;

use App\Actions\CheckLowStockAction;
use App\Http\Requests\ProductMovement\StoreProductMovementRequest;
use App\Http\Resources\ProductMovement\ProductMovementResource;
use App\Models\Product\Scopes\BySearch as ProductBySearch;
use App\Models\ProductMovement;
use App\Models\ProductMovementType;
use App\Models\ProductPresentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ProductMovement::with([
            'product',
            'productPresentation.presentation',
            'productMovementType',
            'user',
        ]);

        if ($request->filled('search')) {
            $query->whereHas('product', fn ($q) => $q->withScopes(new ProductBySearch($request->string('search'))));
        }

        return ProductMovementResource::collection(
            $query->latest()->paginate(30)
        );
    }

    public function store(StoreProductMovementRequest $request, CheckLowStockAction $checkLowStock): JsonResponse
    {
        $presentation = ProductPresentation::where('uuid', $request->input('product_presentation_id'))->firstOrFail();
        $type = ProductMovementType::where('uuid', $request->input('product_movement_type_id'))->firstOrFail();
        $qty = abs((float) $request->input('quantity'));

        $delta = $type->is_income ? $qty : -$qty;
        $presentation->increment('stock', $delta);

        $movement = ProductMovement::create([
            'product_id' => $presentation->product_id,
            'product_presentation_id' => $presentation->id,
            'product_movement_type_id' => $type->id,
            'user_id' => $request->user()->id,
            'quantity' => $qty,
            'notes' => $request->input('notes'),
        ]);

        $checkLowStock->handle($presentation->fresh());

        return ProductMovementResource::make(
            $movement->load(['product', 'productPresentation.presentation', 'productMovementType', 'user'])
        )->response()->setStatusCode(201);
    }

    public function destroy(ProductMovement $productMovement): JsonResponse
    {
        $type = $productMovement->productMovementType;
        $delta = $type->is_income
            ? -$productMovement->quantity
            : $productMovement->quantity;

        $productMovement->productPresentation->increment('stock', $delta);
        $productMovement->delete();

        return response()->json([], 204);
    }
}
