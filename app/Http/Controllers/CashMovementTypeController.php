<?php

namespace App\Http\Controllers;

use App\Actions\CashMovementType\CreateCashMovementTypeAction;
use App\Actions\CashMovementType\DeleteCashMovementTypeAction;
use App\Actions\CashMovementType\ListCashMovementTypesAction;
use App\Actions\CashMovementType\UpdateCashMovementTypeAction;
use App\DTOs\CashMovementType\CreateCashMovementTypeDTO;
use App\DTOs\CashMovementType\UpdateCashMovementTypeDTO;
use App\Http\Requests\CashMovementType\StoreCashMovementTypeRequest;
use App\Http\Requests\CashMovementType\UpdateCashMovementTypeRequest;
use App\Http\Resources\CashMovementType\CashMovementTypeResource;
use App\Models\CashMovementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashMovementTypeController extends Controller
{
    public function index(
        Request $request,
        ListCashMovementTypesAction $action,
    ): AnonymousResourceCollection {
        $paginator = $action->execute($request->input('search'));

        return CashMovementTypeResource::collection($paginator);
    }

    public function store(
        StoreCashMovementTypeRequest $request,
        CreateCashMovementTypeAction $action,
    ): CashMovementTypeResource {
        $type = $action->execute(CreateCashMovementTypeDTO::fromRequest($request));

        return CashMovementTypeResource::make($type);
    }

    public function update(
        UpdateCashMovementTypeRequest $request,
        CashMovementType $cashMovementType,
        UpdateCashMovementTypeAction $action,
    ): CashMovementTypeResource {
        $type = $action->execute($cashMovementType, UpdateCashMovementTypeDTO::fromRequest($request));

        return CashMovementTypeResource::make($type);
    }

    public function destroy(
        CashMovementType $cashMovementType,
        DeleteCashMovementTypeAction $action,
    ): JsonResponse {
        $action->execute($cashMovementType);

        return response()->json([], 204);
    }
}
