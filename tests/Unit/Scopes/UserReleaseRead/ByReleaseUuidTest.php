<?php

namespace Tests\Unit\Scopes\UserReleaseRead;

use App\Models\UserReleaseRead;
use App\Models\UserReleaseRead\Scopes\ByReleaseUuid;
use Illuminate\Support\Str;
use Tests\Unit\Models\ModelTestCase;

class ByReleaseUuidTest extends ModelTestCase
{
    public function test_filters_by_release_uuid(): void
    {
        $uuid = (string) Str::uuid();
        $match = UserReleaseRead::factory()->create(['release_uuid' => $uuid]);
        UserReleaseRead::factory()->create();

        $results = UserReleaseRead::query()->withScopes(new ByReleaseUuid($uuid))->get();

        $this->assertCount(1, $results);
        $this->assertSame($match->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        UserReleaseRead::factory()->create();

        $results = UserReleaseRead::query()->withScopes(new ByReleaseUuid((string) Str::uuid()))->get();

        $this->assertCount(0, $results);
    }
}
