<?php

namespace App\Http\Controllers;

use App\Adapters\MercadoPagoAdapter;
use App\Exceptions\MercadoPagoException;
use App\Models\MercadoPagoCredential;
use Illuminate\Http\JsonResponse;

class MercadoPagoTerminalController extends Controller
{
    public function __construct(private MercadoPagoAdapter $adapter) {}

    public function index(): JsonResponse
    {
        $credential = MercadoPagoCredential::query()->first();

        if (! $credential) {
            throw MercadoPagoException::notConnected();
        }

        $terminals = collect($this->adapter->listTerminals($credential->access_token))
            ->map(fn (array $terminal) => [
                'id' => $terminal['id'],
                'pos_id' => $terminal['pos_id'] ?? null,
                'store_id' => $terminal['store_id'] ?? null,
                'external_pos_id' => $terminal['external_pos_id'] ?? null,
                'operating_mode' => $terminal['operating_mode'] ?? null,
            ])
            ->values();

        return response()->json(['data' => $terminals]);
    }
}
