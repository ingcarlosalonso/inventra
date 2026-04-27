<?php

namespace Tests\Unit\Requests\Client;

use App\Http\Requests\Client\UpdateClientRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateClientRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdateClientRequest)->rules();
    }

    public function test_first_name_is_required(): void
    {
        $v = Validator::make(['last_name' => 'García'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('first_name', $v->errors()->toArray());
    }

    public function test_last_name_is_required(): void
    {
        $v = Validator::make(['first_name' => 'Pedro'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('last_name', $v->errors()->toArray());
    }

    public function test_first_name_max_255(): void
    {
        $v = Validator::make(['first_name' => str_repeat('a', 256), 'last_name' => 'García'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_last_name_max_255(): void
    {
        $v = Validator::make(['first_name' => 'Pedro', 'last_name' => str_repeat('a', 256)], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_email_must_be_valid(): void
    {
        $v = Validator::make(['first_name' => 'Pedro', 'last_name' => 'García', 'email' => 'not-an-email'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['first_name' => 'Pedro', 'last_name' => 'García', 'is_active' => 'yes'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_optional_fields_are_nullable(): void
    {
        $v = Validator::make(['first_name' => 'Pedro', 'last_name' => 'García'], $this->rules());
        $this->assertTrue($v->passes());
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make([
            'first_name' => 'Pedro',
            'last_name' => 'García',
            'email' => 'pedro@email.com',
            'phone' => '1122334455',
            'address' => 'Av. Siempre Viva 742',
            'is_active' => true,
        ], $this->rules());
        $this->assertTrue($v->passes());
    }
}
