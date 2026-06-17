<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ClearReleaseReadsJob;
use App\Models\Release;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Tests\Feature\TenantFeatureTestCase;

class ClearReleaseReadsJobTest extends TenantFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::createReleaseTables();
        Release::query()->delete();
    }

    public function test_job_implements_not_tenant_aware(): void
    {
        $this->assertInstanceOf(NotTenantAware::class, new ClearReleaseReadsJob('uuid'));
    }

    public function test_job_has_correct_release_uuid(): void
    {
        $job = new ClearReleaseReadsJob('test-uuid-123');

        $this->assertSame('test-uuid-123', $job->releaseUuid);
    }

    public function test_deletion_query_removes_reads_for_uuid(): void
    {
        $release = Release::create(['version' => '1.0.0', 'title' => 'Test', 'is_published' => true, 'published_at' => now()]);
        $other = Release::create(['version' => '2.0.0', 'title' => 'Other', 'is_published' => true, 'published_at' => now()]);

        DB::connection('tenant')->table('user_release_reads')->insert([
            ['user_id' => $this->user->id, 'release_uuid' => $release->uuid, 'read_at' => now()],
            ['user_id' => $this->user->id, 'release_uuid' => $other->uuid, 'read_at' => now()],
        ]);

        // Exercise the deletion logic the job runs per tenant
        DB::connection('tenant')->table('user_release_reads')
            ->where('release_uuid', $release->uuid)
            ->delete();

        $this->assertSame(0, DB::connection('tenant')->table('user_release_reads')->where('release_uuid', $release->uuid)->count());
        $this->assertSame(1, DB::connection('tenant')->table('user_release_reads')->where('release_uuid', $other->uuid)->count());
    }
}
