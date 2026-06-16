<?php

namespace Tests\Unit\Models\UserReleaseRead;

use App\Models\Model;
use App\Models\UserReleaseRead;
use Illuminate\Support\Carbon;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(UserReleaseRead::tableName(), [
            'id', 'user_id', 'release_uuid', 'read_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new UserReleaseRead);
    }

    public function test_it_casts_read_at_to_datetime(): void
    {
        $read = UserReleaseRead::factory()->create();

        $this->assertInstanceOf(Carbon::class, $read->read_at);
    }
}
