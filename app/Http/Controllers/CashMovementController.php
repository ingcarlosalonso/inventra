<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashMovement\StoreCashMovementRequest;
use App\Http\Resources\CashMovement\CashMovementResource;
use App\Models\CashMovement;
use App\Models\CashMovementType;
use App\Models\DailyCash;
use Illuminate\Http\JsonResponse;

class CashMovementController extends Controller
{
    public function store(StoreCashMovementRequest $request, DailyCash $dailyCash): JsonResponse
    {
        if ($dailyCash->is_closed) {
            return response()->json(['message' => __('daily_cashes.already_closed')], 422);
        }

        $cashMovementType = CashMovementType::where('uuid', $request->cash_movement_type_id)->firstOrFail();

        $movement = CashMovement::create([
            'daily_cash_id' => $dailyCash->id,
            'cash_movement_type_id' => $cashMovementType->id,
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'notes' => $request->notes,
        ]);

        return CashMovementResource::make($movement->load('cashMovementType', 'user'))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(DailyCash $dailyCash, CashMovement $cashMovement): JsonResponse
    {
        if ($cashMovement->daily_cash_id !== $dailyCash->id) {
            return response()->json(['message' => __('common.not_found')], 404);
        }

        if ($dailyCash->is_closed) {
            return response()->json(['message' => __('daily_cashes.already_closed')], 422);
        }

        $cashMovement->delete();

        return response()->json([], 204);
    }
}
