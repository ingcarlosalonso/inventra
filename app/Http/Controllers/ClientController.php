<?php

namespace App\Http\Controllers;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\DeleteClientAction;
use App\Actions\Client\ListClientsAction;
use App\Actions\Client\UpdateClientAction;
use App\DTOs\Client\CreateClientDTO;
use App\DTOs\Client\UpdateClientDTO;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\Client\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    public function index(
        Request $request,
        ListClientsAction $action,
    ): AnonymousResourceCollection {
        $paginator = $action->execute($request->input('search'));

        return ClientResource::collection($paginator);
    }

    public function store(
        StoreClientRequest $request,
        CreateClientAction $action,
    ): ClientResource {
        $client = $action->execute(CreateClientDTO::fromRequest($request));

        return ClientResource::make($client);
    }

    public function update(
        UpdateClientRequest $request,
        Client $client,
        UpdateClientAction $action,
    ): ClientResource {
        $client = $action->execute($client, UpdateClientDTO::fromRequest($request));

        return ClientResource::make($client);
    }

    public function destroy(
        Client $client,
        DeleteClientAction $action,
    ): JsonResponse {
        $action->execute($client);

        return response()->json([], 204);
    }
}
