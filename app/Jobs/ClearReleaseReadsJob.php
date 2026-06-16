<?php

namespace App\Jobs;

use App\Models\UserReleaseRead;
use App\Models\UserReleaseRead\Scopes\ByReleaseUuid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
                UserReleaseRead::withScopes(new ByReleaseUuid($this->releaseUuid))->delete();
            });
        });
    }
}
