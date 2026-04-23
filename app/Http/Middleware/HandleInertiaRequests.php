<?php

namespace App\Http\Middleware;

use App\Models\Customization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        if (app()->isLocal()) {
            return null;
        }

        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->uuid,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ],
            'translations' => $this->loadTranslations(),
            'locale' => app()->getLocale(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'customization' => $this->loadCustomization(),
        ]);
    }

    private function loadCustomization(): array
    {
        try {
            $c = Customization::firstOrCreate([]);

            return [
                'logo_url' => $c->logo_path ? asset('storage/'.$c->logo_path) : null,
                'primary_color' => $c->primary_color,
                'secondary_color' => $c->secondary_color,
                'accent_color' => $c->accent_color,
                'font_family' => $c->font_family,
            ];
        } catch (\Throwable) {
            return [
                'logo_url' => null,
                'primary_color' => '#3B82F6',
                'secondary_color' => '#1E40AF',
                'accent_color' => '#F59E0B',
                'font_family' => 'Inter',
            ];
        }
    }

    private function loadTranslations(): array
    {
        $locale = app()->getLocale();
        $path = lang_path($locale);

        if (! File::isDirectory($path)) {
            return [];
        }

        $translations = [];

        foreach (File::files($path) as $file) {
            $key = $file->getFilenameWithoutExtension();
            $translations[$key] = require $file->getPathname();
        }

        return $translations;
    }
}
