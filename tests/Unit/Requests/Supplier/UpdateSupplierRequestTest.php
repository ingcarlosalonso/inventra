<?php

namespace Tests\Unit\Requests\Supplier;

use App\Http\Requests\Supplier\UpdateSupplierRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateSupplierRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdateSupplierRequest)->rules();
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

    public function test_email_must_be_valid(): void
    {
        $v = Validator::make(['name' => 'Proveedor A', 'email' => 'bad-email'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['name' => 'Proveedor A', 'is_active' => 'yes'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_optional_fields_are_nullable(): void
    {
        $v = Validator::make(['name' => 'Proveedor A'], $this->rules());
        $this->assertTrue($v->passes());
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make([
            'name' => 'Distribuidora Norte',
            'contact_name' => 'Juan Pérez',
            'email' => 'juan@norte.com',
            'phone' => '1122334455',
            'is_active' => true,
        ], $this->rules());
        $this->assertTrue($v->passes());
    }
}
