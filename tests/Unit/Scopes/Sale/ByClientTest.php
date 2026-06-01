<?php

namespace Tests\Unit\Scopes\Sale;

use App\Models\Client;
use App\Models\Sale;
use App\Models\Sale\Scopes\ByClient;
use Tests\Unit\Models\ModelTestCase;

class ByClientTest extends ModelTestCase
{
    public function test_filters_by_client_id(): void
    {
        $client = Client::factory()->create();
        $other = Client::factory()->create();

        $match = Sale::factory()->create(['client_id' => $client->id]);
        Sale::factory()->create(['client_id' => $other->id]);

        $results = Sale::query()->withScopes(new ByClient($client->id))->get();

        $this->assertCount(1, $results);
        $this->assertSame($match->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Sale::factory()->create();

        $results = Sale::query()->withScopes(new ByClient(99999))->get();

        $this->assertCount(0, $results);
    }
}
