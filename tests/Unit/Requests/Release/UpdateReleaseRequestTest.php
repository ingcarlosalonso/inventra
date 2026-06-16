<?php

namespace Tests\Unit\Requests\Release;

use App\Http\Requests\Release\UpdateReleaseRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateReleaseRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdateReleaseRequest)->rules();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'In-ventra 1.0.0',
            'summary' => 'Summary',
            'items' => [
                ['type' => 'fix', 'title' => 'Fixed thing', 'order' => 0],
            ],
        ], $overrides);
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make($this->validPayload(), $this->rules());
        $this->assertTrue($v->passes());
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
    }

    public function test_does_not_require_version(): void
    {
        $this->assertArrayNotHasKey('version', $this->rules());
    }
}
