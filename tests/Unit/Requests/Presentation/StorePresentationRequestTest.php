<?php

namespace Tests\Unit\Requests\Presentation;

use App\Http\Requests\Presentation\StorePresentationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePresentationRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new StorePresentationRequest)->rules();
    }

    public function test_presentation_type_id_is_required(): void
    {
        $v = Validator::make(['quantity' => 500], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('presentation_type_id', $v->errors()->toArray());
    }

    public function test_quantity_is_required(): void
    {
        $v = Validator::make(['presentation_type_id' => 'some-uuid'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('quantity', $v->errors()->toArray());
    }

    public function test_quantity_must_be_positive(): void
    {
        $v = Validator::make(['presentation_type_id' => 'some-uuid', 'quantity' => 0], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('quantity', $v->errors()->toArray());
    }

    public function test_quantity_must_be_at_least_0_001(): void
    {
        $v = Validator::make(['presentation_type_id' => 'some-uuid', 'quantity' => 0.0001], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['presentation_type_id' => 'some-uuid', 'quantity' => 500, 'is_active' => 'yes'], $this->rules());
        $this->assertFalse($v->passes());
    }
}
