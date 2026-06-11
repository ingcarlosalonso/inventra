<?php

namespace App\Http\Controllers;

use App\Http\Requests\PointOfSale\IndexPointOfSaleRequest;
use App\Http\Requests\PointOfSale\StorePointOfSaleRequest;
use App\Http\Requests\PointOfSale\UpdatePointOfSaleRequest;
use App\Http\Resources\PointOfSale\PointOfSaleResource;
use App\Models\PointOfSale;
use App\Models\PointOfSale\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PointOfSaleController extends Controller
{
    public function index(IndexPointOfSaleRequest $request): AnonymousResourceCollection
    {
        $query = PointOfSale::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return PointOfSaleResource::collection(
            $query->orderBy('number')->get()
        );
    }

    public function store(StorePointOfSaleRequest $request): JsonResponse
    {
        return PointOfSaleResource::make(
            PointOfSale::create($request->validated())
        )->response()->setStatusCode(201);
    }

    public function update(UpdatePointOfSaleRequest $request, PointOfSale $pointOfSale): PointOfSaleResource
    {
        $pointOfSale->update($request->validated());

        return PointOfSaleResource::make($pointOfSale->fresh());
    }

    public function destroy(PointOfSale $pointOfSale): JsonResponse
    {
        $pointOfSale->delete();

        return response()->json([], 204);
    }

    public function toggle(PointOfSale $pointOfSale): PointOfSaleResource
    {
        $pointOfSale->update(['is_active' => ! $pointOfSale->is_active]);

        return PointOfSaleResource::make($pointOfSale->fresh());
    }
}
