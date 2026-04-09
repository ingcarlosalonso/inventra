<?php

namespace App\Http\Controllers;

use App\Http\Requests\Currency\IndexCurrencyRequest;
use App\Http\Requests\Currency\StoreCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Http\Resources\Currency\CurrencyResource;
use App\Models\Currency;
use App\Models\Currency\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CurrencyController extends Controller
{
    public function index(IndexCurrencyRequest $request): AnonymousResourceCollection
    {
        $query = Currency::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return CurrencyResource::collection($query->orderBy('name')->get());
    }

    public function store(StoreCurrencyRequest $request): CurrencyResource
    {
        if ($request->boolean('is_default')) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        return CurrencyResource::make(Currency::create($request->validated()));
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): CurrencyResource
    {
        if ($request->boolean('is_default') && ! $currency->is_default) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        $currency->update($request->validated());

        return CurrencyResource::make($currency->fresh());
    }

    public function destroy(Currency $currency): JsonResponse
    {
        $currency->delete();

        return response()->json([], 204);
    }

    public function toggle(Currency $currency): CurrencyResource
    {
        $currency->update(['is_active' => ! $currency->is_active]);

        return CurrencyResource::make($currency->fresh());
    }
}
