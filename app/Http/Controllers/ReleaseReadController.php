<?php

namespace App\Http\Controllers;

use App\Models\Release;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReleaseReadController extends Controller
{
    public function store(Request $request, string $uuid): JsonResponse
    {
        $request->merge(['uuid' => $uuid]);

        $request->validate([
            'uuid' => ['required', 'uuid', Rule::exists(Release::class, 'uuid')],
        ]);

        DB::connection('tenant')->table('user_release_reads')->updateOrInsert(
            ['user_id' => $request->user()->id, 'release_uuid' => $uuid],
            ['read_at' => now()]
        );

        return response()->json([], 204);
    }
}
