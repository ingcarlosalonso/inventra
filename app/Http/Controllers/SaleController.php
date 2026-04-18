<?php

namespace App\Http\Controllers;

use App\Actions\StoreSaleAction;
use App\Http\Requests\Sale\IndexSaleRequest;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Http\Resources\Sale\SaleResource;
use App\Models\Sale;
use App\Models\Sale\Scopes\BySearch;
use App\Models\Sale\Scopes\ByState;
use App\Models\SaleState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function index(IndexSaleRequest $request): AnonymousResourceCollection
    {
        $query = Sale::with(['client', 'pointOfSale', 'saleState', 'user'])
            ->withSum('payments', 'amount');

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        if ($request->filled('sale_state_id')) {
            $stateId = SaleState::where('uuid', $request->string('sale_state_id'))->value('id');
            if ($stateId) {
                $query->withScopes(new ByState($stateId));
            }
        }

        return SaleResource::collection(
            $query->orderBy('id', 'desc')->paginate(20)
        );
    }

    public function store(StoreSaleRequest $request, StoreSaleAction $action): JsonResponse
    {
        $sale = $action->execute($request->validated(), $request->user()->id);

        return SaleResource::make($sale)->response()->setStatusCode(201);
    }

    public function show(Sale $sale): SaleResource
    {
        return SaleResource::make(
            $sale->load([
                'client',
                'pointOfSale',
                'saleState',
                'currency',
                'user',
                'items.productPresentation.product',
                'items.productPresentation.presentation',
                'payments.paymentMethod',
                'payments.currency',
            ])
        );
    }

    public function destroy(Sale $sale): JsonResponse
    {
        $sale->delete();

        return response()->json([], 204);
    }
}
