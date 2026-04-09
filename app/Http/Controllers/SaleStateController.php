<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleState\IndexSaleStateRequest;
use App\Http\Requests\SaleState\StoreSaleStateRequest;
use App\Http\Requests\SaleState\UpdateSaleStateRequest;
use App\Http\Resources\SaleState\SaleStateResource;
use App\Models\SaleState;
use App\Models\SaleState\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleStateController extends Controller
{
    public function index(IndexSaleStateRequest $request): AnonymousResourceCollection
    {
        $query = SaleState::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return SaleStateResource::collection(
            $query->orderBy('sort_order')->orderBy('name')->get()
        );
    }

    public function store(StoreSaleStateRequest $request): JsonResponse
    {
        if ($request->boolean('is_default')) {
            SaleState::where('is_default', true)->update(['is_default' => false]);
        }

        return SaleStateResource::make(
            SaleState::create($request->validated())
        )->response()->setStatusCode(201);
    }

    public function update(UpdateSaleStateRequest $request, SaleState $saleState): SaleStateResource
    {
        if ($request->boolean('is_default') && ! $saleState->is_default) {
            SaleState::where('is_default', true)->update(['is_default' => false]);
        }

        $saleState->update($request->validated());

        return SaleStateResource::make($saleState->fresh());
    }

    public function destroy(SaleState $saleState): JsonResponse
    {
        $saleState->delete();

        return response()->json([], 204);
    }

    public function toggle(SaleState $saleState): SaleStateResource
    {
        $saleState->update(['is_active' => ! $saleState->is_active]);

        return SaleStateResource::make($saleState->fresh());
    }
}
