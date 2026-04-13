<?php

namespace Tests\Unit\Requests\Product;

use App\Http\Requests\Product\IndexProductRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexProductRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new IndexProductRequest)->rules();
    }

    public function test_search_is_nullable(): void
    {
        $v = Validator::make(['search' => null], $this->rules());
        $this->assertTrue($v->passes());
    }

    public function test_search_max_255(): void
    {
        $v = Validator::make(['search' => str_repeat('a', 256)], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make(['search' => 'Yerba'], $this->rules());
        $this->assertTrue($v->passes());
    }

    public function test_empty_payload_passes(): void
    {
        $v = Validator::make([], $this->rules());
        $this->assertTrue($v->passes());
    }
}
