<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\Client\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class ClientResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $client = Client::factory()->create();
        $resource = ClientResource::make($client)->toArray(new Request);

        foreach (['id', 'name', 'email', 'phone', 'address', 'notes', 'is_active', 'created_at', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $resource);
        }
    }

    public function test_id_is_uuid(): void
    {
        $client = Client::factory()->create();
        $resource = ClientResource::make($client)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }
}
