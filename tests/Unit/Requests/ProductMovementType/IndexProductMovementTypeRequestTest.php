<?php

namespace Tests\Unit\Requests\ProductMovementType;

use App\Http\Requests\ProductMovementType\IndexProductMovementTypeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexProductMovementTypeRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new IndexProductMovementTypeRequest)->rules();
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

    public function test_no_params_passes(): void
    {
        $v = Validator::make([], $this->rules());
        $this->assertTrue($v->passes());
    }
}
