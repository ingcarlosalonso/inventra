<?php

namespace Tests\Unit\Requests\Client;

use App\Http\Requests\Client\StoreClientRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreClientRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new StoreClientRequest)->rules();
    }

    public function test_name_is_required(): void
    {
        $v = Validator::make(['name' => ''], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_email_must_be_valid(): void
    {
        $v = Validator::make(['name' => 'Pedro', 'email' => 'bad'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_all_optional_fields_nullable(): void
    {
        $v = Validator::make(['name' => 'Pedro'], $this->rules());
        $this->assertTrue($v->passes());
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make([
            'name' => 'Pedro García',
            'email' => 'pedro@email.com',
            'phone' => '1122334455',
            'address' => 'Av. Siempre Viva 742',
        ], $this->rules());
        $this->assertTrue($v->passes());
    }
}
