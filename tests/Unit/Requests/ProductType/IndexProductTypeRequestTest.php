<?php

namespace Tests\Unit\Requests\ProductType;

use App\Http\Requests\ProductType\IndexProductTypeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexProductTypeRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new IndexProductTypeRequest)->rules();
    }

    public function test_search_is_optional(): void
    {
        $v = Validator::make([], $this->rules());
        $this->assertTrue($v->passes());
    }

    public function test_search_must_be_string(): void
    {
        $v = Validator::make(['search' => ['array']], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_search_max_255(): void
    {
        $v = Validator::make(['search' => str_repeat('a', 256)], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_valid_search_passes(): void
    {
        $v = Validator::make(['search' => 'electrónica'], $this->rules());
        $this->assertTrue($v->passes());
    }
}
