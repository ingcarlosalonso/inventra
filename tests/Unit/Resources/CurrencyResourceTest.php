<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\Currency\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class CurrencyResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $currency = Currency::factory()->create();
        $resource = CurrencyResource::make($currency)->toArray(new Request);

        foreach (['id', 'name', 'symbol', 'iso_code', 'is_default', 'is_active', 'created_at', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $resource);
        }
    }

    public function test_id_is_uuid(): void
    {
        $currency = Currency::factory()->create();
        $resource = CurrencyResource::make($currency)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }
}
