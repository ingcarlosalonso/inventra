<?php

namespace App\Http\Controllers;

use App\Actions\StoreOrderAction;
use App\Http\Requests\Order\IndexOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStateRequest;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Models\Order\Scopes\BySearch;
use App\Models\Order\Scopes\ByState;
use App\Models\OrderState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(IndexOrderRequest $request): AnonymousResourceCollection
    {
        $query = Order::with(['client', 'courier', 'orderState', 'user']);

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        if ($request->filled('order_state_id')) {
            $stateId = OrderState::where('uuid', $request->string('order_state_id'))->value('id');
            if ($stateId) {
                $query->withScopes(new ByState($stateId));
            }
        }

        return OrderResource::collection(
            $query->orderBy('id', 'desc')->paginate(20)
        );
    }

    public function store(StoreOrderRequest $request, StoreOrderAction $action): JsonResponse
    {
        $order = $action->execute($request->validated(), $request->user()->id);

        return OrderResource::make($order)->response()->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        return OrderResource::make(
            $order->load([
                'client',
                'courier',
                'orderState',
                'pointOfSale',
                'sale',
                'currency',
                'user',
                'items.productPresentation.product',
                'items.productPresentation.presentation',
                'payments.paymentMethod',
                'payments.currency',
            ])
        );
    }

    public function updateState(Order $order, UpdateOrderStateRequest $request): OrderResource
    {
        $orderStateId = OrderState::where('uuid', $request->string('order_state_id'))->value('id');
        $order->update(['order_state_id' => $orderStateId]);

        return OrderResource::make($order->load(['orderState']));
    }

    public function destroy(Order $order): JsonResponse
    {
        $order->delete();

        return response()->json([], 204);
    }
}
