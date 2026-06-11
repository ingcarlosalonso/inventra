<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\Presentation\PresentationResource;
use App\Models\Presentation;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class PresentationResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $presentation = Presentation::factory()->create();
        $presentation->load('presentationType');
        $resource = PresentationResource::make($presentation)->toArray(new Request);

        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('quantity', $resource);
        $this->assertArrayHasKey('is_active', $resource);
        $this->assertArrayHasKey('created_at', $resource);
        $this->assertArrayHasKey('updated_at', $resource);
    }

    public function test_id_is_uuid_not_integer(): void
    {
        $presentation = Presentation::factory()->create();
        $resource = PresentationResource::make($presentation)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }

    public function test_display_included_when_presentation_type_loaded(): void
    {
        $presentation = Presentation::factory()->create();
        $presentation->load('presentationType');
        $resource = PresentationResource::make($presentation)->toArray(new Request);

        $this->assertArrayHasKey('display', $resource);
        $this->assertNotNull($resource['display']);
    }
}
