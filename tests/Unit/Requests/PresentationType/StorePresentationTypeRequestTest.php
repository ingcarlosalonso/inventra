<?php

namespace Tests\Unit\Requests\PresentationType;

use App\Http\Requests\PresentationType\StorePresentationTypeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePresentationTypeRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new StorePresentationTypeRequest)->rules();
    }

    public function test_name_is_required(): void
    {
        $v = Validator::make(['abbreviation' => 'kg'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_name_max_255(): void
    {
        $v = Validator::make(['name' => str_repeat('a', 256), 'abbreviation' => 'kg'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_abbreviation_is_required(): void
    {
        $v = Validator::make(['name' => 'Kilogramos'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('abbreviation', $v->errors()->toArray());
    }

    public function test_abbreviation_max_20(): void
    {
        $v = Validator::make(['name' => 'Test', 'abbreviation' => str_repeat('a', 21)], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['name' => 'Kilogramos', 'abbreviation' => 'kg', 'is_active' => 'maybe'], $this->rules());
        $this->assertFalse($v->passes());
    }
}
