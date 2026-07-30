<?php

namespace Tests\Unit\Models\Client\Scopes;

use App\Models\Client;
use App\Models\Client\Scopes\BySearch;
use Tests\Unit\Models\ModelTestCase;

class BySearchTest extends ModelTestCase
{
    public function test_filters_by_name(): void
    {
        Client::factory()->create(['first_name' => 'Juan', 'last_name' => 'Pérez']);
        Client::factory()->create(['first_name' => 'María', 'last_name' => 'García']);

        $results = Client::withScopes(new BySearch('Pérez'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Juan Pérez', $results->first()->full_name);
    }

    public function test_filters_by_email(): void
    {
        Client::factory()->create(['first_name' => 'Cliente', 'last_name' => 'A', 'email' => 'contacto@empresa.com']);
        Client::factory()->create(['first_name' => 'Cliente', 'last_name' => 'B', 'email' => 'otro@mail.com']);

        $results = Client::withScopes(new BySearch('empresa'))->get();

        $this->assertCount(1, $results);
    }

    public function test_filters_by_phone(): void
    {
        Client::factory()->create(['first_name' => 'Cliente', 'last_name' => 'C', 'phone' => '011-4444-1234']);
        Client::factory()->create(['first_name' => 'Cliente', 'last_name' => 'D', 'phone' => '011-5555-9876']);

        $results = Client::withScopes(new BySearch('4444'))->get();

        $this->assertCount(1, $results);
    }

    public function test_returns_empty_when_no_match(): void
    {
        Client::factory()->create(['first_name' => 'Juan', 'last_name' => 'Nadie']);

        $results = Client::withScopes(new BySearch('xyz_no_match'))->get();

        $this->assertCount(0, $results);
    }

    public function test_filters_by_full_name_with_first_and_last_name_in_any_order(): void
    {
        $target = Client::factory()->create(['first_name' => 'Luciana', 'last_name' => 'Fernández']);
        Client::factory()->create(['first_name' => 'Luciana', 'last_name' => 'Gomez']);
        Client::factory()->create(['first_name' => 'Martín', 'last_name' => 'Fernández']);

        $byFirstLast = Client::withScopes(new BySearch('Luciana Fernández'))->get();
        $byLastFirst = Client::withScopes(new BySearch('Fernández Luciana'))->get();

        $this->assertCount(1, $byFirstLast);
        $this->assertSame($target->id, $byFirstLast->first()->id);
        $this->assertCount(1, $byLastFirst);
        $this->assertSame($target->id, $byLastFirst->first()->id);
    }
}
