<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReleaseRead\StoreReleaseReadRequest;
use App\Models\UserReleaseRead;
use App\Models\UserReleaseRead\Scopes\ByReleaseUuid;
use App\Models\UserReleaseRead\Scopes\ByUser;
use Illuminate\Http\JsonResponse;

class ReleaseReadController extends Controller
{
    public function store(StoreReleaseReadRequest $request, string $uuid): JsonResponse
    {
        $userId = $request->user()->id;

        $read = UserReleaseRead::withScopes([new ByUser($userId), new ByReleaseUuid($uuid)])->first();

        if ($read) {
            $read->update(['read_at' => now()]);
        } else {
            UserReleaseRead::create([
                'user_id' => $userId,
                'release_uuid' => $uuid,
                'read_at' => now(),
            ]);
        }

        return response()->json([], 204);
    }
}
