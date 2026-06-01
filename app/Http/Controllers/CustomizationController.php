<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomizationRequest;
use App\Http\Resources\Customization\CustomizationResource;
use App\Models\Customization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CustomizationController extends Controller
{
    public function show(): JsonResponse
    {
        return CustomizationResource::make(Customization::firstOrCreate([]))->response()->setStatusCode(200);
    }

    public function update(UpdateCustomizationRequest $request): JsonResponse
    {
        $customization = Customization::firstOrCreate([]);

        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->boolean('remove_logo')) {
            if ($customization->logo_path) {
                Storage::disk('public')->delete($customization->logo_path);
            }
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($customization->logo_path) {
                Storage::disk('public')->delete($customization->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('customizations', 'public');
        }

        $customization->update($data);

        return CustomizationResource::make($customization)->response()->setStatusCode(200);
    }
}
