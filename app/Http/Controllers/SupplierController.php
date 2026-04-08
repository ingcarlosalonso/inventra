<?php

namespace App\Http\Controllers;

use App\Actions\Supplier\CreateSupplierAction;
use App\Actions\Supplier\DeleteSupplierAction;
use App\Actions\Supplier\ListSuppliersAction;
use App\Actions\Supplier\UpdateSupplierAction;
use App\DTOs\Supplier\CreateSupplierDTO;
use App\DTOs\Supplier\UpdateSupplierDTO;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\Supplier\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function index(
        Request $request,
        ListSuppliersAction $action,
    ): AnonymousResourceCollection {
        $paginator = $action->execute($request->input('search'));

        return SupplierResource::collection($paginator);
    }

    public function store(
        StoreSupplierRequest $request,
        CreateSupplierAction $action,
    ): SupplierResource {
        $supplier = $action->execute(CreateSupplierDTO::fromRequest($request));

        return SupplierResource::make($supplier);
    }

    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier,
        UpdateSupplierAction $action,
    ): SupplierResource {
        $supplier = $action->execute($supplier, UpdateSupplierDTO::fromRequest($request));

        return SupplierResource::make($supplier);
    }

    public function destroy(
        Supplier $supplier,
        DeleteSupplierAction $action,
    ): JsonResponse {
        $action->execute($supplier);

        return response()->json([], 204);
    }
}
