<?php

namespace App\Http\Controllers;

use App\Http\Requests\Brand\IndexBrandRequest;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\Brand\BrandResource;
use App\Models\Brand;
use App\Models\Brand\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    public function index(IndexBrandRequest $request): AnonymousResourceCollection
    {
        $query = Brand::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return BrandResource::collection($query->orderBy('name')->get());
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        return BrandResource::make(Brand::create($request->validated()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): BrandResource
    {
        $brand->update($request->validated());

        return BrandResource::make($brand->fresh());
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return response()->json([], 204);
    }

    public function toggle(Brand $brand): BrandResource
    {
        $brand->update(['is_active' => ! $brand->is_active]);

        return BrandResource::make($brand->fresh());
    }
}
