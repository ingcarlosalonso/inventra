<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\IndexSupplierRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\Supplier\SupplierResource;
use App\Models\Supplier;
use App\Models\Supplier\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function index(IndexSupplierRequest $request): AnonymousResourceCollection
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return SupplierResource::collection(
            $query->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        return SupplierResource::make(
            Supplier::create($request->validated())
        )->response()->setStatusCode(201);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update($request->validated());

        return SupplierResource::make($supplier->fresh());
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json([], 204);
    }
}
