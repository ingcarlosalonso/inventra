<?php

namespace Tests\Feature\Controllers;

use App\Models\Barcode;
use App\Models\ProductPresentation;
use Tests\Feature\TenantFeatureTestCase;

class ProductPresentationBarcodeControllerTest extends TenantFeatureTestCase
{
    public function test_show_returns_product_presentation_for_valid_barcode(): void
    {
        $pp = ProductPresentation::factory()->create();
        Barcode::factory()->create([
            'product_presentation_id' => $pp->id,
            'barcode' => '7791234567890',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/barcode/7791234567890')
            ->assertOk();

        $response->assertJsonPath('data.id', $pp->uuid);
    }

    public function test_show_returns_product_name_and_presentation(): void
    {
        $pp = ProductPresentation::factory()->create();
        Barcode::factory()->create([
            'product_presentation_id' => $pp->id,
            'barcode' => '1112223334445',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/barcode/1112223334445')
            ->assertOk();

        $response->assertJsonStructure(['data' => ['id', 'price', 'stock', 'product', 'presentation']]);
        $this->assertNotNull($response->json('data.product.name'));
    }

    public function test_show_returns_404_for_unknown_barcode(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/barcode/NONEXISTENT')
            ->assertNotFound()
            ->assertJsonPath('message', __('products.barcode_not_found'));
    }

    public function test_show_requires_auth(): void
    {
        $this->getJson('/api/v1/products/barcode/1234567890123')
            ->assertUnauthorized();
    }
}
