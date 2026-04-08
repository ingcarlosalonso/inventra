<?php

namespace App\Http\Controllers;

use App\Actions\ProductMovementType\CreateProductMovementTypeAction;
use App\Actions\ProductMovementType\DeleteProductMovementTypeAction;
use App\Actions\ProductMovementType\ListProductMovementTypesAction;
use App\Actions\ProductMovementType\UpdateProductMovementTypeAction;
use App\DTOs\ProductMovementType\CreateProductMovementTypeDTO;
use App\DTOs\ProductMovementType\UpdateProductMovementTypeDTO;
use App\Http\Requests\ProductMovementType\StoreProductMovementTypeRequest;
use App\Http\Requests\ProductMovementType\UpdateProductMovementTypeRequest;
use App\Http\Resources\ProductMovementType\ProductMovementTypeResource;
use App\Models\ProductMovementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductMovementTypeController extends Controller
{
    public function index(
        Request $request,
        ListProductMovementTypesAction $action,
    ): AnonymousResourceCollection {
        $paginator = $action->execute($request->input('search'));

        return ProductMovementTypeResource::collection($paginator);
    }

    public function store(
        StoreProductMovementTypeRequest $request,
        CreateProductMovementTypeAction $action,
    ): ProductMovementTypeResource {
        $type = $action->execute(CreateProductMovementTypeDTO::fromRequest($request));

        return ProductMovementTypeResource::make($type);
    }

    public function update(
        UpdateProductMovementTypeRequest $request,
        ProductMovementType $productMovementType,
        UpdateProductMovementTypeAction $action,
    ): ProductMovementTypeResource {
        $type = $action->execute($productMovementType, UpdateProductMovementTypeDTO::fromRequest($request));

        return ProductMovementTypeResource::make($type);
    }

    public function destroy(
        ProductMovementType $productMovementType,
        DeleteProductMovementTypeAction $action,
    ): JsonResponse {
        $action->execute($productMovementType);

        return response()->json([], 204);
    }
}
