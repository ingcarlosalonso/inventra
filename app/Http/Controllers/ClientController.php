<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\IndexClientRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\Client\ClientResource;
use App\Models\Client;
use App\Models\Client\Scopes\BySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    public function index(IndexClientRequest $request): AnonymousResourceCollection
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return ClientResource::collection(
            $query->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreClientRequest $request): ClientResource
    {
        return ClientResource::make(
            Client::create($request->validated())
        );
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $client->update($request->validated());

        return ClientResource::make($client->fresh());
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json([], 204);
    }
}
