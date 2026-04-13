<?php

namespace App\Http\Controllers;

use App\Actions\StoreReceptionAction;
use App\Http\Requests\Reception\IndexReceptionRequest;
use App\Http\Requests\Reception\StoreReceptionRequest;
use App\Http\Resources\Reception\ReceptionResource;
use App\Models\Reception;
use App\Models\Reception\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReceptionController extends Controller
{
    public function index(IndexReceptionRequest $request): AnonymousResourceCollection
    {
        $query = Reception::with(['supplier', 'user', 'items.productPresentation.presentation.presentationType', 'items.productPresentation.product']);

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return ReceptionResource::collection(
            $query->orderBy('received_at', 'desc')->orderBy('id', 'desc')->paginate(20)
        );
    }

    public function store(StoreReceptionRequest $request, StoreReceptionAction $action): JsonResponse
    {
        $reception = $action->execute($request->validated(), $request->user()->id);

        return ReceptionResource::make($reception)->response()->setStatusCode(201);
    }

    public function show(Reception $reception): ReceptionResource
    {
        return ReceptionResource::make(
            $reception->load(['supplier', 'user', 'items.productPresentation.presentation.presentationType', 'items.productPresentation.product'])
        );
    }

    public function destroy(Reception $reception): JsonResponse
    {
        $reception->delete();

        return response()->json([], 204);
    }
}
