<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assistant\ChatAssistantRequest;
use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;

class AssistantController extends Controller
{
    public function chat(ChatAssistantRequest $request, AssistantService $service): JsonResponse
    {
        $reply = $service->chat($request->validated('messages'));

        return response()->json(['message' => $reply]);
    }
}
