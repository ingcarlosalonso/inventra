<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Spatie\Multitenancy\Models\Tenant;

class ClearReleaseReadsJob implements NotTenantAware, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $releaseUuid) {}

    public function handle(): void
    {
        Tenant::all()->each(function (Tenant $tenant) {
            $tenant->execute(function () {
                DB::connection('tenant')->table('user_release_reads')
                    ->where('release_uuid', $this->releaseUuid)
                    ->delete();
            });
        });
    }
}
