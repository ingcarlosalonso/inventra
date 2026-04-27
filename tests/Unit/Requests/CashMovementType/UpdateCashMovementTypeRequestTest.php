<?php

namespace Tests\Unit\Requests\CashMovementType;

use App\Http\Requests\CashMovementType\UpdateCashMovementTypeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateCashMovementTypeRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdateCashMovementTypeRequest)->rules();
    }

    public function test_name_is_required(): void
    {
        $v = Validator::make(['name' => '', 'is_income' => true], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_name_max_255(): void
    {
        $v = Validator::make(['name' => str_repeat('a', 256), 'is_income' => true], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_income_is_required(): void
    {
        $v = Validator::make(['name' => 'Retiro'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('is_income', $v->errors()->toArray());
    }

    public function test_is_income_must_be_boolean(): void
    {
        $v = Validator::make(['name' => 'Retiro', 'is_income' => 'maybe'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['name' => 'Retiro', 'is_income' => true, 'is_active' => 'yes'], $this->rules());
        $this->assertFalse($v->passes());
    }
}
