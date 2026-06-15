<?php

namespace App\Http\Middleware;

use App\Models\Customization;
use App\Models\Release;
use App\Models\Release\Scopes\Published;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;
use Spatie\Multitenancy\Models\Tenant;

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
            'unread_release' => $this->loadUnreadRelease($request),
        ]);
    }

    private function loadUnreadRelease(Request $request): ?array
    {
        if (! $request->user() || ! Tenant::current()) {
            return null;
        }

        try {
            $latest = Release::withScopes(new Published)
                ->with('items')
                ->latest('published_at')
                ->first();

            if (! $latest) {
                return null;
            }

            $alreadyRead = DB::connection('tenant')
                ->table('user_release_reads')
                ->where('user_id', $request->user()->id)
                ->where('release_uuid', $latest->uuid)
                ->exists();

            if ($alreadyRead) {
                return null;
            }

            return [
                'uuid' => $latest->uuid,
                'version' => $latest->version,
                'title' => $latest->title,
                'summary' => $latest->summary,
                'published_at' => $latest->published_at?->toISOString(),
                'items' => $latest->items->map(fn ($item) => [
                    'type' => $item->type,
                    'title' => $item->title,
                ])->values()->toArray(),
            ];
        } catch (\Throwable) {
            return null;
        }
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
