<?php

namespace Tests\Unit\Requests\PresentationType;

use App\Http\Requests\PresentationType\UpdatePresentationTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdatePresentationTypeRequestTest extends TestCase
{
    private function rules(): array
    {
        $request = new UpdatePresentationTypeRequest;
        $request->setRouteResolver(fn () => tap(new Route('PUT', '', []), function ($route) {
            $route->bind(new Request);
        }));

        return $request->rules();
    }

    public function test_name_is_required(): void
    {
        $v = Validator::make(['abbreviation' => 'kg'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_abbreviation_is_required(): void
    {
        $v = Validator::make(['name' => 'Kilogramos'], $this->rules());
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('abbreviation', $v->errors()->toArray());
    }

    public function test_abbreviation_max_20(): void
    {
        $v = Validator::make(['name' => 'Test', 'abbreviation' => str_repeat('a', 21)], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make(['name' => 'Kilogramos', 'abbreviation' => 'kg', 'is_active' => 'invalid'], $this->rules());
        $this->assertFalse($v->passes());
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make(['name' => 'Kilogramos', 'abbreviation' => 'kg'], $this->rules());
        $this->assertTrue($v->passes());
    }
}
