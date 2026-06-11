<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => __('auth.failed')], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return response()->json(['message' => __('auth.inactive')], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()?->delete();
        Auth::logout();

        return response()->json([], 204);
    }
}
