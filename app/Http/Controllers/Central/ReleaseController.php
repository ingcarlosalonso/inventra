<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Release\StoreReleaseRequest;
use App\Http\Requests\Release\UpdateReleaseRequest;
use App\Jobs\ClearReleaseReadsJob;
use App\Models\Release;
use App\Models\Release\Scopes\ByVersion;
use App\Services\ChangelogParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function index(ChangelogParser $parser): Response
    {
        $parsed = $parser->parseLatest();
        $version = $parsed['version'] ?? config('app.version');

        $release = Release::withScopes(new ByVersion($version))->with('items')->first();

        if ($release) {
            return Inertia::render('Central/Releases/Index', [
                'saved' => true,
                'release' => [
                    'id' => $release->id,
                    'uuid' => $release->uuid,
                    'version' => $release->version,
                    'title' => $release->title,
                    'summary' => $release->summary,
                    'is_published' => $release->is_published,
                    'published_at' => $release->published_at?->toISOString(),
                    'items' => $release->items->map(fn ($item) => [
                        'id' => $item->id,
                        'type' => $item->type,
                        'title' => $item->title,
                        'order' => $item->order,
                    ])->values(),
                ],
            ]);
        }

        return Inertia::render('Central/Releases/Index', [
            'saved' => false,
            'release' => $parsed ? [
                'id' => null,
                'uuid' => null,
                'version' => $parsed['version'],
                'title' => $parsed['title'],
                'summary' => $parsed['summary'],
                'is_published' => false,
                'published_at' => null,
                'items' => collect($parsed['items'])->map(fn ($item) => array_merge($item, ['id' => null]))->values(),
            ] : [
                'id' => null,
                'uuid' => null,
                'version' => $version,
                'title' => "In-ventra {$version}",
                'summary' => '',
                'is_published' => false,
                'published_at' => null,
                'items' => [],
            ],
        ]);
    }

    public function store(StoreReleaseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $release = Release::create([
                'version' => $data['version'],
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
                'is_published' => false,
            ]);

            foreach ($data['items'] as $item) {
                $release->items()->create([
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'order' => $item['order'],
                ]);
            }
        });

        return back()->with('success', __('releases.saved'));
    }

    public function update(UpdateReleaseRequest $request, Release $release): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $release) {
            $release->update([
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
            ]);

            $release->items()->delete();

            foreach ($data['items'] as $item) {
                $release->items()->create([
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'order' => $item['order'],
                ]);
            }
        });

        return back()->with('success', __('releases.saved'));
    }

    public function publish(Release $release): RedirectResponse
    {
        $release->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        ClearReleaseReadsJob::dispatchSync($release->uuid);

        return back()->with('success', __('releases.published'));
    }

    public function unpublish(Release $release): RedirectResponse
    {
        $release->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return back()->with('success', __('releases.unpublished'));
    }
}
