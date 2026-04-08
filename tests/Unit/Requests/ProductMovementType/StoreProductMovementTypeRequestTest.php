<?php

namespace Tests\Unit\Requests\ProductMovementType;

use App\Http\Requests\ProductMovementType\StoreProductMovementTypeRequest;
use App\Models\ProductMovementType;
use Illuminate\Support\Facades\Validator;
use Tests\Unit\Models\ModelTestCase;

class StoreProductMovementTypeRequestTest extends ModelTestCase
{
    private function rules(): array
    {
        return (new StoreProductMovementTypeRequest)->rules();
    }

    public function test_name_is_required(): void
    {
        $validator = Validator::make([], $this->rules());

        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_is_income_is_required(): void
    {
        $validator = Validator::make(['name' => 'Test'], $this->rules());

        $this->assertTrue($validator->errors()->has('is_income'));
    }

    public function test_name_must_be_unique(): void
    {
        ProductMovementType::factory()->create(['name' => 'Existing']);

        $validator = Validator::make(
            ['name' => 'Existing', 'is_income' => true],
            $this->rules()
        );

        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_valid_data_passes(): void
    {
        $validator = Validator::make(
            ['name' => 'New Type', 'is_income' => true],
            $this->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_is_active_is_optional(): void
    {
        $validator = Validator::make(
            ['name' => 'New Type', 'is_income' => false],
            $this->rules()
        );

        $this->assertTrue($validator->passes());
    }
}
