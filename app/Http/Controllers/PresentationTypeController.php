<?php

namespace App\Http\Controllers;

use App\Http\Requests\PresentationType\IndexPresentationTypeRequest;
use App\Http\Requests\PresentationType\StorePresentationTypeRequest;
use App\Http\Requests\PresentationType\UpdatePresentationTypeRequest;
use App\Http\Resources\PresentationType\PresentationTypeResource;
use App\Models\PresentationType;
use App\Models\PresentationType\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PresentationTypeController extends Controller
{
    public function index(IndexPresentationTypeRequest $request): AnonymousResourceCollection
    {
        $query = PresentationType::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return PresentationTypeResource::collection($query->orderBy('name')->get());
    }

    public function store(StorePresentationTypeRequest $request): JsonResponse
    {
        return PresentationTypeResource::make(PresentationType::create($request->validated()))
            ->response()->setStatusCode(201);
    }

    public function update(UpdatePresentationTypeRequest $request, PresentationType $presentationType): PresentationTypeResource
    {
        $presentationType->update($request->validated());

        return PresentationTypeResource::make($presentationType->fresh());
    }

    public function destroy(PresentationType $presentationType): JsonResponse
    {
        if ($presentationType->presentations()->exists()) {
            return response()->json(['message' => __('presentation_types.has_presentations_error')], 422);
        }

        $presentationType->delete();

        return response()->json([], 204);
    }

    public function toggle(PresentationType $presentationType): PresentationTypeResource
    {
        $presentationType->update(['is_active' => ! $presentationType->is_active]);

        return PresentationTypeResource::make($presentationType->fresh());
    }
}
