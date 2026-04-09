<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethod\IndexPaymentMethodRequest;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethod\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\PaymentMethod\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentMethodController extends Controller
{
    public function index(IndexPaymentMethodRequest $request): AnonymousResourceCollection
    {
        $query = PaymentMethod::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return PaymentMethodResource::collection(
            $query->orderBy('name')->get()
        );
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        return PaymentMethodResource::make(
            PaymentMethod::create($request->validated())
        )->response()->setStatusCode(201);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): PaymentMethodResource
    {
        $paymentMethod->update($request->validated());

        return PaymentMethodResource::make($paymentMethod->fresh());
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();

        return response()->json([], 204);
    }

    public function toggle(PaymentMethod $paymentMethod): PaymentMethodResource
    {
        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        return PaymentMethodResource::make($paymentMethod->fresh());
    }
}
