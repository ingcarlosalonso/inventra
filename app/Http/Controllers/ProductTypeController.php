<?php

namespace App\Http\Controllers;

use App\Actions\ProductType\CreateProductTypeAction;
use App\Actions\ProductType\DeleteProductTypeAction;
use App\Actions\ProductType\ListProductTypesAction;
use App\Actions\ProductType\ToggleProductTypeAction;
use App\Actions\ProductType\UpdateProductTypeAction;
use App\DTOs\ProductType\CreateProductTypeDTO;
use App\DTOs\ProductType\UpdateProductTypeDTO;
use App\Exceptions\ProductTypeException;
use App\Http\Requests\ProductType\StoreProductTypeRequest;
use App\Http\Requests\ProductType\UpdateProductTypeRequest;
use App\Http\Resources\ProductType\ProductTypeResource;
use App\Models\ProductType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductTypeController extends Controller
{
    public function index(
        Request $request,
        ListProductTypesAction $action,
    ): AnonymousResourceCollection {
        $types = $action->execute($request->input('search'));

        return ProductTypeResource::collection($types);
    }

    public function store(
        StoreProductTypeRequest $request,
        CreateProductTypeAction $action,
    ): ProductTypeResource {
        $type = $action->execute(CreateProductTypeDTO::fromRequest($request));

        return ProductTypeResource::make($type);
    }

    public function update(
        UpdateProductTypeRequest $request,
        ProductType $productType,
        UpdateProductTypeAction $action,
    ): ProductTypeResource|JsonResponse {
        try {
            $type = $action->execute($productType, UpdateProductTypeDTO::fromRequest($request));
        } catch (ProductTypeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        return ProductTypeResource::make($type);
    }

    public function destroy(
        ProductType $productType,
        DeleteProductTypeAction $action,
    ): JsonResponse {
        try {
            $action->execute($productType);
        } catch (ProductTypeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        return response()->json([], 204);
    }

    public function toggle(
        ProductType $productType,
        ToggleProductTypeAction $action,
    ): ProductTypeResource {
        $type = $action->execute($productType);

        return ProductTypeResource::make($type);
    }
}
