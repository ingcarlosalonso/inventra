<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function chat(Request $request, AssistantService $service): JsonResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $reply = $service->chat($validated['messages']);

        return response()->json(['message' => $reply]);
    }
}
