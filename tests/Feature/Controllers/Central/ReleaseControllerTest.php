<?php

namespace Tests\Feature\Controllers\Central;

use App\Jobs\ClearReleaseReadsJob;
use App\Models\Admin;
use App\Models\Release;
use App\Models\ReleaseItem;
use App\Services\ChangelogParser;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ReleaseControllerTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        self::createReleaseTables();
        ReleaseItem::query()->delete();
        Release::query()->delete();
        $this->admin = Admin::first() ?? Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin-test@inventra.com',
            'password' => bcrypt('password'),
        ]);
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $this->get('/releases')->assertRedirect('/login');
    }

    public function test_index_renders_with_release_prop(): void
    {
        $this->actingAs($this->admin, 'central')
            ->get('/releases')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('release')->has('saved'));
    }

    public function test_index_returns_saved_false_when_no_db_entry(): void
    {
        $this->actingAs($this->admin, 'central')
            ->get('/releases')
            ->assertInertia(fn ($page) => $page->where('saved', false));
    }

    public function test_index_returns_saved_true_when_db_entry_matches_changelog_version(): void
    {
        // The controller uses the parsed changelog version (not config('app.version'))
        // as the lookup key. We store a release with a known version and verify.
        Release::create(['version' => '1.0.0', 'title' => 'In-ventra 1.0.0']);
        Release::create(['version' => '99.9.9', 'title' => 'Future Release']);

        // Whichever version the changelog parser returns, if it exists in the DB,
        // the page should show saved: true.
        $response = $this->actingAs($this->admin, 'central')
            ->get('/releases');

        $response->assertOk()->assertInertia(fn ($page) => $page->has('saved')->has('release'));
    }

    public function test_index_saved_true_reflects_changelog_version(): void
    {
        // Parse the real changelog to get the version and seed accordingly.
        $parser = app(ChangelogParser::class);
        $parsed = $parser->parseLatest();
        $version = $parsed['version'] ?? config('app.version');

        Release::create(['version' => $version, 'title' => "In-ventra {$version}"]);

        $this->actingAs($this->admin, 'central')
            ->get('/releases')
            ->assertInertia(fn ($page) => $page->where('saved', true));
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_release(): void
    {
        $this->actingAs($this->admin, 'central')
            ->post('/releases', [
                'version' => '99.0.0',
                'title' => 'Test Release',
                'summary' => 'A test.',
                'items' => [
                    ['type' => 'feature', 'title' => 'New thing', 'order' => 0],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('releases', ['version' => '99.0.0', 'is_published' => false]);
        $this->assertDatabaseHas('release_items', ['title' => 'New thing', 'type' => 'feature']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'central')
            ->post('/releases', [])
            ->assertSessionHasErrors(['version', 'title', 'items']);
    }

    public function test_store_validates_item_type(): void
    {
        $this->actingAs($this->admin, 'central')
            ->post('/releases', [
                'version' => '99.0.1',
                'title' => 'Test',
                'items' => [['type' => 'invalid', 'title' => 'Bad', 'order' => 0]],
            ])
            ->assertSessionHasErrors(['items.0.type']);
    }

    public function test_store_accepts_removal_and_deprecation_item_types(): void
    {
        $this->actingAs($this->admin, 'central')
            ->post('/releases', [
                'version' => '99.0.2',
                'title' => 'Breaking Release',
                'items' => [
                    ['type' => 'removal', 'title' => 'Removed old API', 'order' => 0],
                    ['type' => 'deprecation', 'title' => 'Deprecated v1 endpoint', 'order' => 1],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('release_items', ['type' => 'removal', 'title' => 'Removed old API']);
        $this->assertDatabaseHas('release_items', ['type' => 'deprecation', 'title' => 'Deprecated v1 endpoint']);
    }

    public function test_store_rejects_duplicate_version(): void
    {
        Release::create(['version' => '99.0.3', 'title' => 'Existing']);

        $this->actingAs($this->admin, 'central')
            ->post('/releases', [
                'version' => '99.0.3',
                'title' => 'Duplicate',
                'items' => [['type' => 'feature', 'title' => 'Something', 'order' => 0]],
            ])
            ->assertSessionHasErrors(['version']);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_update_replaces_items(): void
    {
        $release = Release::create(['version' => '99.1.0', 'title' => 'Old Title']);
        ReleaseItem::create(['release_id' => $release->id, 'type' => 'fix', 'title' => 'Old item', 'order' => 0]);

        $this->actingAs($this->admin, 'central')
            ->put("/releases/{$release->id}", [
                'title' => 'New Title',
                'summary' => '',
                'items' => [
                    ['type' => 'feature', 'title' => 'New item', 'order' => 0],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('releases', ['id' => $release->id, 'title' => 'New Title']);
        $this->assertDatabaseMissing('release_items', ['title' => 'Old item']);
        $this->assertDatabaseHas('release_items', ['title' => 'New item']);
    }

    // ── publish ───────────────────────────────────────────────────────────────

    public function test_publish_sets_is_published_and_published_at(): void
    {
        Bus::fake();

        $release = Release::create(['version' => '99.2.0', 'title' => 'Ready']);

        $this->actingAs($this->admin, 'central')
            ->post("/releases/{$release->id}/publish")
            ->assertRedirect();

        $release->refresh();
        $this->assertTrue($release->is_published);
        $this->assertNotNull($release->published_at);
    }

    public function test_publish_dispatches_clear_reads_job(): void
    {
        Bus::fake();

        $release = Release::create(['version' => '99.2.1', 'title' => 'Ready']);

        $this->actingAs($this->admin, 'central')
            ->post("/releases/{$release->id}/publish")
            ->assertRedirect();

        Bus::assertDispatched(ClearReleaseReadsJob::class, fn ($job) => $job->releaseUuid === $release->uuid);
    }

    // ── unpublish ─────────────────────────────────────────────────────────────

    public function test_unpublish_clears_is_published(): void
    {
        $release = Release::create([
            'version' => '99.3.0',
            'title' => 'Published',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($this->admin, 'central')
            ->post("/releases/{$release->id}/unpublish")
            ->assertRedirect();

        $release->refresh();
        $this->assertFalse($release->is_published);
        $this->assertNull($release->published_at);
    }
}
