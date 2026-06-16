<?php

namespace Tests\Unit\Requests\Release;

use App\Http\Requests\Release\StoreReleaseRequest;
use App\Models\Release;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreReleaseRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::createReleaseTables();
        Release::query()->delete();
    }

    private function rules(): array
    {
        return (new StoreReleaseRequest)->rules();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'version' => '1.0.0',
            'title' => 'In-ventra 1.0.0',
            'summary' => 'Summary',
            'items' => [
                ['type' => 'feature', 'title' => 'New thing', 'order' => 0],
            ],
        ], $overrides);
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make($this->validPayload(), $this->rules());
        $this->assertTrue($v->passes());
    }

    public function test_version_is_required(): void
    {
        $v = Validator::make($this->validPayload(['version' => '']), $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('version', $v->errors()->toArray());
    }

    public function test_version_must_be_unique(): void
    {
        Release::create(['version' => '1.0.0', 'title' => 'Existing']);

        $v = Validator::make($this->validPayload(), $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('version', $v->errors()->toArray());
    }

    public function test_title_is_required(): void
    {
        $v = Validator::make($this->validPayload(['title' => '']), $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_items_is_required(): void
    {
        $v = Validator::make($this->validPayload(['items' => []]), $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_item_type_must_be_valid(): void
    {
        $v = Validator::make(
            $this->validPayload(['items' => [['type' => 'invalid', 'title' => 'Bad', 'order' => 0]]]),
            $this->rules()
        );

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('items.0.type', $v->errors()->toArray());
    }
}
