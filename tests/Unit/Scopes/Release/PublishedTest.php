<?php

namespace Tests\Unit\Scopes\Release;

use App\Models\Release;
use App\Models\Release\Scopes\Published;
use Tests\TestCase;

class PublishedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::createReleaseTables();
        Release::query()->delete();
    }

    public function test_returns_only_published_releases(): void
    {
        $published = Release::create(['version' => '1.0.0', 'title' => 'A', 'is_published' => true, 'published_at' => now()]);
        Release::create(['version' => '1.1.0', 'title' => 'B', 'is_published' => false]);

        $results = Release::withScopes(new Published)->get();

        $this->assertCount(1, $results);
        $this->assertSame($published->id, $results->first()->id);
    }

    public function test_returns_empty_when_none_published(): void
    {
        Release::create(['version' => '1.0.0', 'title' => 'Draft', 'is_published' => false]);

        $results = Release::withScopes(new Published)->get();

        $this->assertCount(0, $results);
    }

    public function test_returns_all_published(): void
    {
        Release::create(['version' => '1.0.0', 'title' => 'A', 'is_published' => true, 'published_at' => now()]);
        Release::create(['version' => '1.1.0', 'title' => 'B', 'is_published' => true, 'published_at' => now()]);
        Release::create(['version' => '1.2.0', 'title' => 'C', 'is_published' => false]);

        $results = Release::withScopes(new Published)->get();

        $this->assertCount(2, $results);
    }
}
