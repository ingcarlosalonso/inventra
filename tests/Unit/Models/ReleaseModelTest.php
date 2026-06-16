<?php

namespace Tests\Unit\Models;

use App\Models\Release;
use App\Models\ReleaseItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReleaseModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('release_items');
        Schema::dropIfExists('releases');

        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('version');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('release_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('title');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('release_items');
        Schema::dropIfExists('releases');
        parent::tearDown();
    }

    public function test_it_has_expected_columns(): void
    {
        $columns = Schema::getColumnListing('releases');

        foreach (['id', 'uuid', 'version', 'title', 'summary', 'is_published', 'published_at', 'created_at', 'updated_at'] as $column) {
            $this->assertContains($column, $columns);
        }
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $release = Release::create(['version' => '9.9.9', 'title' => 'Test Release']);

        $this->assertNotNull($release->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $release->uuid
        );
    }

    public function test_is_published_defaults_to_false(): void
    {
        $release = Release::create(['version' => '9.9.8', 'title' => 'Draft Release']);

        $this->assertFalse($release->is_published);
    }

    public function test_has_items_relationship(): void
    {
        $release = Release::create(['version' => '9.9.7', 'title' => 'Test']);

        ReleaseItem::create(['release_id' => $release->id, 'type' => 'feature', 'title' => 'New thing', 'order' => 0]);

        $this->assertCount(1, $release->fresh()->items);
    }

    public function test_items_are_ordered_by_order_column(): void
    {
        $release = Release::create(['version' => '9.9.6', 'title' => 'Test']);

        ReleaseItem::create(['release_id' => $release->id, 'type' => 'fix', 'title' => 'Second', 'order' => 1]);
        ReleaseItem::create(['release_id' => $release->id, 'type' => 'feature', 'title' => 'First', 'order' => 0]);

        $this->assertSame('First', $release->fresh()->items->first()->title);
    }
}
