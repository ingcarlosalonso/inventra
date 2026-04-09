<?php

namespace App\Http\Middleware;

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
        ]);
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
