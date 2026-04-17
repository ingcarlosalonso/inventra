<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyCash\CloseDailyCashRequest;
use App\Http\Requests\DailyCash\IndexDailyCashRequest;
use App\Http\Requests\DailyCash\StoreDailyCashRequest;
use App\Http\Requests\DailyCash\UpdateDailyCashRequest;
use App\Http\Resources\DailyCash\DailyCashResource;
use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\BySearch;
use App\Models\DailyCash\Scopes\ByStatus;
use App\Models\PointOfSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DailyCashController extends Controller
{
    public function index(IndexDailyCashRequest $request): AnonymousResourceCollection
    {
        $query = DailyCash::with('pointOfSale');

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        if ($request->filled('is_closed')) {
            $query->withScopes(new ByStatus($request->boolean('is_closed')));
        }

        return DailyCashResource::collection(
            $query->orderByDesc('opened_at')->paginate(20)
        );
    }

    public function store(StoreDailyCashRequest $request): JsonResponse
    {
        $pointOfSale = PointOfSale::where('uuid', $request->point_of_sale_id)->firstOrFail();

        $dailyCash = DailyCash::create([
            'point_of_sale_id' => $pointOfSale->id,
            'user_id' => auth()->id(),
            'opening_balance' => $request->opening_balance,
            'opened_at' => $request->opened_at ?? now(),
            'notes' => $request->notes,
            'is_closed' => false,
        ]);

        return DailyCashResource::make($dailyCash->load('pointOfSale'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(DailyCash $dailyCash): DailyCashResource
    {
        $dailyCash->load([
            'pointOfSale',
            'cashMovements.cashMovementType',
            'cashMovements.user',
        ]);

        return DailyCashResource::make($dailyCash);
    }

    public function update(UpdateDailyCashRequest $request, DailyCash $dailyCash): JsonResponse
    {
        if ($dailyCash->is_closed) {
            return response()->json(['message' => __('daily_cashes.already_closed')], 422);
        }

        $dailyCash->update($request->validated());

        return DailyCashResource::make($dailyCash->fresh('pointOfSale'))
            ->response();
    }

    public function close(CloseDailyCashRequest $request, DailyCash $dailyCash): JsonResponse
    {
        if ($dailyCash->is_closed) {
            return response()->json(['message' => __('daily_cashes.already_closed')], 422);
        }

        $dailyCash->update([
            'closing_balance' => $request->closing_balance,
            'closed_at' => now(),
            'is_closed' => true,
            'notes' => $request->notes ?? $dailyCash->notes,
        ]);

        return DailyCashResource::make($dailyCash->fresh('pointOfSale'))
            ->response();
    }

    public function destroy(DailyCash $dailyCash): JsonResponse
    {
        if ($dailyCash->is_closed) {
            return response()->json(['message' => __('daily_cashes.cannot_delete_closed')], 422);
        }

        $dailyCash->delete();

        return response()->json([], 204);
    }
}
