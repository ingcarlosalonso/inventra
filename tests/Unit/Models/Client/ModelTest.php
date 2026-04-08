<?php

namespace Tests\Unit\Models\Client;

use App\Models\Client;
use App\Models\Model;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Client::tableName(), [
            'id', 'uuid', 'name', 'email', 'phone', 'address', 'notes',
            'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Client);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $client = Client::factory()->create();

        $this->assertNotNull($client->uuid);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $client = Client::factory()->create();

        $this->assertTrue($client->is_active);
    }
}
