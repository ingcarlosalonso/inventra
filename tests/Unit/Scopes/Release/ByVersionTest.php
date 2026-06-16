<?php

namespace Tests\Unit\Scopes\Release;

use App\Models\Release;
use App\Models\Release\Scopes\ByVersion;
use Tests\TestCase;

class ByVersionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::createReleaseTables();
        Release::query()->delete();
    }

    public function test_filters_by_version(): void
    {
        $target = Release::create(['version' => '1.0.0', 'title' => 'Target']);
        Release::create(['version' => '2.0.0', 'title' => 'Other']);

        $results = Release::withScopes(new ByVersion('1.0.0'))->get();

        $this->assertCount(1, $results);
        $this->assertSame($target->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Release::create(['version' => '1.0.0', 'title' => 'A']);

        $results = Release::withScopes(new ByVersion('9.9.9'))->get();

        $this->assertCount(0, $results);
    }
}
