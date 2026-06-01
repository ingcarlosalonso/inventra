<?php

namespace Tests\Unit\Requests\Supplier;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreSupplierRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new StoreSupplierRequest)->rules();
    }

    public function test_name_is_required(): void
    {
        $v = Validator::make(['name' => ''], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_email_must_be_valid(): void
    {
        $v = Validator::make(['name' => 'ACME', 'email' => 'not-an-email'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_email_is_nullable(): void
    {
        $v = Validator::make(['name' => 'ACME', 'email' => null], $this->rules());
        $this->assertTrue($v->passes());
    }

    public function test_valid_full_payload_passes(): void
    {
        $v = Validator::make([
            'name' => 'ACME Corp',
            'contact_name' => 'Juan',
            'email' => 'juan@acme.com',
            'phone' => '1234567890',
            'address' => 'Calle 123',
        ], $this->rules());
        $this->assertTrue($v->passes());
    }
}
