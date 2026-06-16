<?php

namespace Tests\Feature\Controllers;

use App\Models\Release;
use Illuminate\Support\Facades\DB;
use Tests\Feature\TenantFeatureTestCase;

class ReleaseReadControllerTest extends TenantFeatureTestCase
{
    private Release $release;

    protected function setUp(): void
    {
        parent::setUp();
        self::createReleaseTables();

        Release::query()->delete();

        $this->release = Release::create([
            'version' => '1.0.0',
            'title' => 'In-ventra 1.0.0',
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function test_store_marks_release_as_read_for_user(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/releases/{$this->release->uuid}/read")
            ->assertNoContent();

        $this->assertTrue(
            DB::connection('tenant')
                ->table('user_release_reads')
                ->where('user_id', $this->user->id)
                ->where('release_uuid', $this->release->uuid)
                ->exists()
        );
    }

    public function test_store_is_idempotent(): void
    {
        DB::connection('tenant')->table('user_release_reads')->insert([
            'user_id' => $this->user->id,
            'release_uuid' => $this->release->uuid,
            'read_at' => now(),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/releases/{$this->release->uuid}/read")
            ->assertNoContent();

        $count = DB::connection('tenant')
            ->table('user_release_reads')
            ->where('user_id', $this->user->id)
            ->where('release_uuid', $this->release->uuid)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson("/api/v1/releases/{$this->release->uuid}/read")
            ->assertUnauthorized();
    }

    public function test_store_rejects_nonexistent_uuid(): void
    {
        $fakeUuid = '00000000-0000-0000-0000-000000000000';

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/releases/{$fakeUuid}/read")
            ->assertUnprocessable();
    }
}
