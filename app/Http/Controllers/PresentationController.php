<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presentation\IndexPresentationRequest;
use App\Http\Requests\Presentation\StorePresentationRequest;
use App\Http\Requests\Presentation\UpdatePresentationRequest;
use App\Http\Resources\Presentation\PresentationResource;
use App\Models\Presentation;
use App\Models\Presentation\Scopes\BySearch;
use App\Models\PresentationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PresentationController extends Controller
{
    public function index(IndexPresentationRequest $request): AnonymousResourceCollection
    {
        $query = Presentation::query()->with('presentationType');

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return PresentationResource::collection($query->orderBy('quantity')->get());
    }

    public function store(StorePresentationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['presentation_type_id'] = PresentationType::where('uuid', $data['presentation_type_id'])->value('id');

        return PresentationResource::make(Presentation::create($data)->load('presentationType'))
            ->response()->setStatusCode(201);
    }

    public function update(UpdatePresentationRequest $request, Presentation $presentation): PresentationResource
    {
        $data = $request->validated();
        $data['presentation_type_id'] = PresentationType::where('uuid', $data['presentation_type_id'])->value('id');

        $presentation->update($data);

        return PresentationResource::make($presentation->fresh()->load('presentationType'));
    }

    public function destroy(Presentation $presentation): JsonResponse
    {
        $presentation->delete();

        return response()->json([], 204);
    }

    public function toggle(Presentation $presentation): PresentationResource
    {
        $presentation->update(['is_active' => ! $presentation->is_active]);

        return PresentationResource::make($presentation->fresh()->load('presentationType'));
    }
}
