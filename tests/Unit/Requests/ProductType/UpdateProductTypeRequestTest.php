<?php

namespace Tests\Unit\Requests\ProductType;

use App\Http\Requests\ProductType\UpdateProductTypeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateProductTypeRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdateProductTypeRequest)->rules();
    }

    public function test_name_is_required(): void
    {
        $v = Validator::make(['name' => ''], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_name_max_255(): void
    {
        $v = Validator::make(['name' => str_repeat('a', 256)], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['name' => 'Electrónica', 'is_active' => 'maybe'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_parent_id_is_nullable(): void
    {
        $v = Validator::make(['name' => 'Electrónica', 'parent_id' => null], $this->rules());
        $this->assertTrue($v->passes());
    }
}
