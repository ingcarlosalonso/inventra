<?php

namespace Tests\Unit\Requests\Currency;

use App\Http\Requests\Currency\UpdateCurrencyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateCurrencyRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdateCurrencyRequest)->rules();
    }

    public function test_name_is_required(): void
    {
        $v = Validator::make(['name' => '', 'symbol' => '$', 'iso_code' => 'ARS'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_symbol_is_required(): void
    {
        $v = Validator::make(['name' => 'Peso', 'symbol' => '', 'iso_code' => 'ARS'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('symbol', $v->errors()->toArray());
    }

    public function test_iso_code_is_required(): void
    {
        $v = Validator::make(['name' => 'Peso', 'symbol' => '$', 'iso_code' => ''], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('iso_code', $v->errors()->toArray());
    }

    public function test_symbol_max_10(): void
    {
        $v = Validator::make(['name' => 'Peso', 'symbol' => str_repeat('$', 11), 'iso_code' => 'ARS'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_iso_code_max_3(): void
    {
        $v = Validator::make(['name' => 'Peso', 'symbol' => '$', 'iso_code' => 'PESO'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['name' => 'Peso', 'symbol' => '$', 'iso_code' => 'ARS', 'is_active' => 'yes'], $this->rules());
        $this->assertFalse($v->passes());
    }
}
