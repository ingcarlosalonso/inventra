<?php

namespace App\Http\Controllers;

use App\Actions\StorePromotionAction;
use App\Actions\UpdatePromotionAction;
use App\Http\Requests\Promotion\IndexPromotionRequest;
use App\Http\Requests\Promotion\StorePromotionRequest;
use App\Http\Requests\Promotion\UpdatePromotionRequest;
use App\Http\Resources\Promotion\PromotionResource;
use App\Models\Promotion;
use App\Models\Promotion\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromotionController extends Controller
{
    public function index(IndexPromotionRequest $request): AnonymousResourceCollection
    {
        $query = Promotion::with('items.product');

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return PromotionResource::collection(
            $query->orderBy('name')->paginate(20)
        );
    }

    public function store(StorePromotionRequest $request, StorePromotionAction $action): JsonResponse
    {
        return PromotionResource::make($action->execute($request->validated()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion, UpdatePromotionAction $action): PromotionResource
    {
        return PromotionResource::make($action->execute($promotion, $request->validated()));
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        $promotion->delete();

        return response()->json([], 204);
    }

    public function toggle(Promotion $promotion): PromotionResource
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);

        return PromotionResource::make(
            $promotion->fresh()->load('items.product')
        );
    }
}
