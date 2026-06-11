<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\PresentationType\PresentationTypeResource;
use App\Models\PresentationType;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class PresentationTypeResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $type = PresentationType::factory()->create();
        $resource = PresentationTypeResource::make($type)->toArray(new Request);

        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('name', $resource);
        $this->assertArrayHasKey('abbreviation', $resource);
        $this->assertArrayHasKey('is_active', $resource);
        $this->assertArrayHasKey('created_at', $resource);
        $this->assertArrayHasKey('updated_at', $resource);
    }

    public function test_id_is_uuid_not_integer(): void
    {
        $type = PresentationType::factory()->create();
        $resource = PresentationTypeResource::make($type)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }
}
