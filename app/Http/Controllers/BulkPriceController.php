<?php

namespace App\Http\Controllers;

use App\Actions\BulkPriceUpdateAction;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Models\Product\Scopes\ByProductType;
use App\Models\Product\Scopes\BySearch as ProductBySearch;
use App\Models\ProductType;
use App\Models\Scopes\Active;
use App\Models\Scopes\ByUuid;
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
        ])->withScopes(new Active);

        if ($request->filled('product_type_id')) {
            $productType = ProductType::query()->withScopes(new ByUuid($request->input('product_type_id')))->first();
            if ($productType) {
                $query->withScopes(new ByProductType($productType->id));
            }
        }

        if ($request->filled('search')) {
            $query->withScopes(new ProductBySearch($request->string('search')));
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
            $validated['product_type_id'] = ProductType::query()->withScopes(new ByUuid($validated['product_type_id']))->value('id');
        }

        $updated = $action->handle($validated);

        return response()->json(['updated' => $updated]);
    }
}
