<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $locale = $request->string('locale')->toString();

        if (! in_array($locale, ['es', 'en'])) {
            return response()->json(['message' => __('common.unsupported_locale')], 422);
        }

        session(['locale' => $locale]);

        return response()->json(['locale' => $locale]);
    }
}
