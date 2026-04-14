<?php

namespace App\Http\Controllers;

use App\Http\Requests\Courier\IndexCourierRequest;
use App\Http\Requests\Courier\StoreCourierRequest;
use App\Http\Requests\Courier\UpdateCourierRequest;
use App\Http\Resources\Courier\CourierResource;
use App\Models\Courier;
use App\Models\Courier\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourierController extends Controller
{
    public function index(IndexCourierRequest $request): AnonymousResourceCollection
    {
        $query = Courier::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return CourierResource::collection(
            $query->orderBy('name')->get()
        );
    }

    public function store(StoreCourierRequest $request): JsonResponse
    {
        return CourierResource::make(
            Courier::create($request->validated())
        )->response()->setStatusCode(201);
    }

    public function update(UpdateCourierRequest $request, Courier $courier): CourierResource
    {
        $courier->update($request->validated());

        return CourierResource::make($courier->fresh());
    }

    public function destroy(Courier $courier): JsonResponse
    {
        $courier->delete();

        return response()->json([], 204);
    }

    public function toggle(Courier $courier): CourierResource
    {
        $courier->update(['is_active' => ! $courier->is_active]);

        return CourierResource::make($courier->fresh());
    }
}
