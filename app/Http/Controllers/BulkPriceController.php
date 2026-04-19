<?php

namespace App\Http\Controllers;

use App\Actions\BulkPriceUpdateAction;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BulkPriceController extends Controller
{
    public function preview(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'product_type_id' => ['nullable', 'string', 'exists:tenant.product_types,uuid'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Product::with([
            'productType',
            'productPresentations.presentation.presentationType',
        ])->where('is_active', true);

        if ($request->filled('product_type_id')) {
            $productType = ProductType::where('uuid', $request->input('product_type_id'))->first();
            $query->where('product_type_id', $productType?->id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        return ProductResource::collection($query->orderBy('name')->get());
    }

    public function update(Request $request, BulkPriceUpdateAction $action): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric'],
            'product_type_id' => ['nullable', 'string', 'exists:tenant.product_types,uuid'],
            'only_active' => ['boolean'],
        ]);

        if (! empty($validated['product_type_id'])) {
            $productType = ProductType::where('uuid', $validated['product_type_id'])->first();
            $validated['product_type_id'] = $productType?->id;
        }

        $updated = $action->handle($validated);

        return response()->json(['updated' => $updated]);
    }
}
