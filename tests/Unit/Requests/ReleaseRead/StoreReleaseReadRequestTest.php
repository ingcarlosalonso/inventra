<?php

namespace Tests\Unit\Requests\ReleaseRead;

use App\Http\Requests\ReleaseRead\StoreReleaseReadRequest;
use App\Models\Release;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreReleaseReadRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::createReleaseTables();
        Release::query()->delete();
    }

    private function rules(): array
    {
        return (new StoreReleaseReadRequest)->rules();
    }

    public function test_valid_existing_uuid_passes(): void
    {
        $release = Release::create(['version' => '1.0.0', 'title' => 'Test']);

        $v = Validator::make(['uuid' => $release->uuid], $this->rules());

        $this->assertTrue($v->passes());
    }

    public function test_uuid_is_required(): void
    {
        $v = Validator::make(['uuid' => ''], $this->rules());

        $this->assertFalse($v->passes());
    }

    public function test_uuid_must_be_valid_uuid_format(): void
    {
        $v = Validator::make(['uuid' => 'not-a-uuid'], $this->rules());

        $this->assertFalse($v->passes());
    }

    public function test_uuid_must_exist(): void
    {
        $v = Validator::make(['uuid' => '00000000-0000-0000-0000-000000000000'], $this->rules());

        $this->assertFalse($v->passes());
    }
}
