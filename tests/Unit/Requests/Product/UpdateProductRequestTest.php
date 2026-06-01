<?php

namespace Tests\Unit\Requests\Product;

use App\Http\Requests\Product\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateProductRequestTest extends TestCase
{
    private function rules(): array
    {
        $fakeProduct = new class
        {
            public int $id = 1;
        };

        $request = new UpdateProductRequest;
        $request->setRouteResolver(fn () => tap(new Route('PUT', 'products/{product}', ['uses' => fn () => null]), function ($route) use ($fakeProduct) {
            $route->bind(new Request);
            $route->setParameter('product', $fakeProduct);
        }));

        return $request->rules();
    }

    public function test_name_is_required(): void
    {
        $v = Validator::make([
            'product_type_id' => 'some-uuid',
            'presentations' => [['presentation_id' => 'uuid', 'price' => 100, 'min_stock' => 5]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_name_max_255(): void
    {
        $v = Validator::make([
            'name' => str_repeat('a', 256),
            'product_type_id' => 'some-uuid',
            'presentations' => [['presentation_id' => 'uuid', 'price' => 100, 'min_stock' => 5]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_product_type_id_is_required(): void
    {
        $v = Validator::make([
            'name' => 'Yerba',
            'presentations' => [['presentation_id' => 'uuid', 'price' => 100, 'min_stock' => 5]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('product_type_id', $v->errors()->toArray());
    }

    public function test_presentations_is_required(): void
    {
        $v = Validator::make([
            'name' => 'Yerba',
            'product_type_id' => 'some-uuid',
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('presentations', $v->errors()->toArray());
    }

    public function test_presentations_must_have_at_least_one(): void
    {
        $v = Validator::make([
            'name' => 'Yerba',
            'product_type_id' => 'some-uuid',
            'presentations' => [],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('presentations', $v->errors()->toArray());
    }

    public function test_presentation_price_must_be_non_negative(): void
    {
        $v = Validator::make([
            'name' => 'Yerba',
            'product_type_id' => 'some-uuid',
            'presentations' => [['presentation_id' => 'uuid', 'price' => -5, 'min_stock' => 0]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('presentations.0.price', $v->errors()->toArray());
    }

    public function test_is_active_must_be_boolean(): void
    {
        $v = Validator::make([
            'name' => 'Yerba',
            'product_type_id' => 'some-uuid',
            'presentations' => [['presentation_id' => 'uuid', 'price' => 100, 'min_stock' => 5]],
            'is_active' => 'invalid',
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('is_active', $v->errors()->toArray());
    }
}
