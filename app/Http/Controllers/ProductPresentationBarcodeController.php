<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductPresentation\ProductPresentationResource;
use App\Models\ProductPresentation;
use App\Models\ProductPresentation\Scopes\ByBarcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductPresentationBarcodeController extends Controller
{
    public function show(Request $request, string $barcode): ProductPresentationResource|JsonResponse
    {
        $productPresentation = ProductPresentation::with([
            'barcodes',
            'presentation.presentationType',
            'product.productType',
        ])->withScopes(new ByBarcode($barcode))->first();

        if (! $productPresentation) {
            return response()->json(['message' => __('products.barcode_not_found')], 404);
        }

        return ProductPresentationResource::make($productPresentation);
    }
}
