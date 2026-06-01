<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderState\IndexOrderStateRequest;
use App\Http\Requests\OrderState\StoreOrderStateRequest;
use App\Http\Requests\OrderState\UpdateOrderStateRequest;
use App\Http\Resources\OrderState\OrderStateResource;
use App\Models\OrderState;
use App\Models\OrderState\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderStateController extends Controller
{
    public function index(IndexOrderStateRequest $request): AnonymousResourceCollection
    {
        $query = OrderState::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return OrderStateResource::collection(
            $query->orderBy('sort_order')->orderBy('name')->get()
        );
    }

    public function store(StoreOrderStateRequest $request): JsonResponse
    {
        if ($request->boolean('is_default')) {
            OrderState::where('is_default', true)->update(['is_default' => false]);
        }

        return OrderStateResource::make(
            OrderState::create($request->validated())
        )->response()->setStatusCode(201);
    }

    public function update(UpdateOrderStateRequest $request, OrderState $orderState): OrderStateResource
    {
        if ($request->boolean('is_default') && ! $orderState->is_default) {
            OrderState::where('is_default', true)->update(['is_default' => false]);
        }

        $orderState->update($request->validated());

        return OrderStateResource::make($orderState->fresh());
    }

    public function destroy(OrderState $orderState): JsonResponse
    {
        $orderState->delete();

        return response()->json([], 204);
    }

    public function toggle(OrderState $orderState): OrderStateResource
    {
        $orderState->update(['is_active' => ! $orderState->is_active]);

        return OrderStateResource::make($orderState->fresh());
    }
}
