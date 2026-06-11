<?php

namespace Tests\Unit\Scopes\Order;

use App\Models\Client;
use App\Models\Order;
use App\Models\Order\Scopes\ByClient;
use Tests\Unit\Models\ModelTestCase;

class ByClientTest extends ModelTestCase
{
    public function test_filters_by_client_id(): void
    {
        $client = Client::factory()->create();
        $other = Client::factory()->create();

        $match = Order::factory()->create(['client_id' => $client->id]);
        Order::factory()->create(['client_id' => $other->id]);

        $results = Order::query()->withScopes(new ByClient($client->id))->get();

        $this->assertCount(1, $results);
        $this->assertSame($match->id, $results->first()->id);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Order::factory()->create();

        $results = Order::query()->withScopes(new ByClient(99999))->get();

        $this->assertCount(0, $results);
    }
}
