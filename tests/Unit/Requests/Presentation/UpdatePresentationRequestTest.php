<?php

namespace Tests\Unit\Requests\Presentation;

use App\Http\Requests\Presentation\UpdatePresentationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdatePresentationRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdatePresentationRequest)->rules();
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
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['presentation_type_id' => 'some-uuid', 'quantity' => 500, 'is_active' => 'no'], $this->rules());
        $this->assertFalse($v->passes());
    }
}
