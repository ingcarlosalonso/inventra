<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ProductPresentation\ProductPresentationResource;
use App\Models\ProductPresentation;
use Illuminate\Http\Request;
use Tests\Unit\Models\ModelTestCase;

class ProductPresentationResourceTest extends ModelTestCase
{
    public function test_it_returns_expected_keys(): void
    {
        $pp = ProductPresentation::factory()->create();
        $resource = ProductPresentationResource::make($pp)->toArray(new Request);

        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('price', $resource);
        $this->assertArrayHasKey('stock', $resource);
        $this->assertArrayHasKey('min_stock', $resource);
        $this->assertArrayHasKey('is_active', $resource);
        $this->assertArrayHasKey('created_at', $resource);
        $this->assertArrayHasKey('updated_at', $resource);
    }

    public function test_id_is_uuid_not_integer(): void
    {
        $pp = ProductPresentation::factory()->create();
        $resource = ProductPresentationResource::make($pp)->toArray(new Request);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $resource['id']
        );
    }

    public function test_presentation_included_when_loaded(): void
    {
        $pp = ProductPresentation::factory()->create();
        $pp->load('presentation.presentationType');
        $resource = ProductPresentationResource::make($pp)->toArray(new Request);

        $this->assertArrayHasKey('presentation', $resource);
        $this->assertArrayHasKey('id', $resource['presentation']);
        $this->assertArrayHasKey('display', $resource['presentation']);
        $this->assertArrayHasKey('quantity', $resource['presentation']);
        $this->assertArrayHasKey('presentation_type', $resource['presentation']);
    }
}
