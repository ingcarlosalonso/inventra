<?php

namespace Tests\Unit\Models\Client\Scopes;

use App\Models\Client;
use App\Models\Client\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        Client::factory()->create(['name' => 'Juan Pérez']);
        Client::factory()->create(['name' => 'María García']);

        $results = Client::withScopes(new BySearch('Pérez'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Juan Pérez', $results->first()->name);
    }

    public function test_filters_by_email(): void
    {
        Client::factory()->create(['name' => 'Cliente A', 'email' => 'contacto@empresa.com']);
        Client::factory()->create(['name' => 'Cliente B', 'email' => 'otro@mail.com']);

        $results = Client::withScopes(new BySearch('empresa'))->get();

        $this->assertCount(1, $results);
    }

    public function test_filters_by_phone(): void
    {
        Client::factory()->create(['name' => 'Cliente C', 'phone' => '011-4444-1234']);
        Client::factory()->create(['name' => 'Cliente D', 'phone' => '011-5555-9876']);

        $results = Client::withScopes(new BySearch('4444'))->get();

        $this->assertCount(1, $results);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Client::factory()->create(['name' => 'Juan']);

        $results = Client::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }
}
