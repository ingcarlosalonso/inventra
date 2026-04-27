<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashMovementType\IndexCashMovementTypeRequest;
use App\Http\Requests\CashMovementType\StoreCashMovementTypeRequest;
use App\Http\Requests\CashMovementType\UpdateCashMovementTypeRequest;
use App\Http\Resources\CashMovementType\CashMovementTypeResource;
use App\Models\CashMovementType;
use App\Models\CashMovementType\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashMovementTypeController extends Controller
{
    public function index(IndexCashMovementTypeRequest $request): AnonymousResourceCollection
    {
        $query = CashMovementType::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return CashMovementTypeResource::collection(
            $query->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreCashMovementTypeRequest $request): JsonResponse
    {
        return CashMovementTypeResource::make(
            CashMovementType::create($request->validated())
        )->response()->setStatusCode(201);
    }

    public function update(UpdateCashMovementTypeRequest $request, CashMovementType $cashMovementType): CashMovementTypeResource
    {
        $cashMovementType->update($request->validated());

        return CashMovementTypeResource::make($cashMovementType->fresh());
    }

    public function destroy(CashMovementType $cashMovementType): JsonResponse
    {
        $cashMovementType->delete();

        return response()->json([], 204);
    }
}
