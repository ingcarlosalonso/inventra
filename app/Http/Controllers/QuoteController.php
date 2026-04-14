<?php

namespace App\Http\Controllers;

use App\Actions\ConvertQuoteToSaleAction;
use App\Actions\StoreQuoteAction;
use App\Http\Requests\Quote\ConvertQuoteRequest;
use App\Http\Requests\Quote\IndexQuoteRequest;
use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Http\Resources\Quote\QuoteResource;
use App\Http\Resources\Sale\SaleResource;
use App\Models\Quote;
use App\Models\Quote\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuoteController extends Controller
{
    public function index(IndexQuoteRequest $request): AnonymousResourceCollection
    {
        $query = Quote::with(['client', 'user']);

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return QuoteResource::collection(
            $query->orderBy('id', 'desc')->paginate(20)
        );
    }

    public function store(StoreQuoteRequest $request, StoreQuoteAction $action): JsonResponse
    {
        $quote = $action->execute($request->validated(), $request->user()->id);

        return QuoteResource::make($quote)->response()->setStatusCode(201);
    }

    public function show(Quote $quote): QuoteResource
    {
        return QuoteResource::make(
            $quote->load([
                'client',
                'currency',
                'user',
                'sale',
                'items.productPresentation.product',
                'items.productPresentation.presentation',
            ])
        );
    }

    public function convert(Quote $quote, ConvertQuoteRequest $request, ConvertQuoteToSaleAction $action): JsonResponse
    {
        if ($quote->isConverted()) {
            return response()->json(['message' => __('quotes.already_converted')], 422);
        }

        $quote->load(['client', 'currency', 'items.productPresentation.product', 'items.productPresentation.presentation']);

        $sale = $action->execute($quote, $request->validated(), $request->user()->id);

        return SaleResource::make($sale->load([
            'client', 'pointOfSale', 'saleState', 'currency', 'user',
            'items.productPresentation.product',
            'items.productPresentation.presentation',
            'payments.paymentMethod',
        ]))->response()->setStatusCode(201);
    }

    public function destroy(Quote $quote): JsonResponse
    {
        $quote->delete();

        return response()->json([], 204);
    }
}
