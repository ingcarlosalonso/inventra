<?php

namespace Tests\Unit\Actions\Assistant;

use App\Actions\Assistant\ResolveSaleableItemMatches;
use App\Enums\SaleItemType;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductPresentation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResolveSaleableItemMatchesTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['tenant'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'in_ventra_testing')]);
        DB::purge('tenant');
        DB::connection('tenant')->beginTransaction();

        self::migrateTenantDb();
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
    }

    private function presentationFor(Product $product, array $attributes = []): ProductPresentation
    {
        return ProductPresentation::factory()->create(array_merge([
            'product_id' => $product->id,
            'presentation_id' => Presentation::factory(),
        ], $attributes));
    }

    public function test_resolves_a_single_unambiguous_match(): void
    {
        $product = Product::factory()->create(['name' => 'Coca-Cola 500ml']);
        $pp = $this->presentationFor($product, ['price' => 999]);

        [$result] = (new ResolveSaleableItemMatches)->execute([
            ['search' => 'Coca-Cola', 'quantity' => 3],
        ]);

        $this->assertSame('resolved', $result['status']);
        $this->assertSame(SaleItemType::Product->value, $result['item_type']);
        $this->assertSame($pp->uuid, $result['saleable_id']);
        $this->assertSame(3.0, $result['quantity']);
        $this->assertSame(999.0, $result['unit_price']);
    }

    public function test_returns_not_found_when_nothing_matches(): void
    {
        [$result] = (new ResolveSaleableItemMatches)->execute([
            ['search' => 'zzz_nonexistent_zzz', 'quantity' => 1],
        ]);

        $this->assertSame('not_found', $result['status']);
        $this->assertNull($result['saleable_id']);
    }

    public function test_returns_ambiguous_with_candidate_labels_when_multiple_match(): void
    {
        $coca = Product::factory()->create(['name' => 'Coca-Cola']);
        $this->presentationFor($coca, ['price' => 500]);
        $this->presentationFor($coca, ['price' => 900]);

        [$result] = (new ResolveSaleableItemMatches)->execute([
            ['search' => 'Coca-Cola', 'quantity' => 1],
        ]);

        $this->assertSame('ambiguous', $result['status']);
        $this->assertNull($result['saleable_id']);
        $this->assertGreaterThanOrEqual(2, count($result['candidates']));
    }

    public function test_excludes_inactive_products_and_presentations(): void
    {
        $inactiveProduct = Product::factory()->create(['name' => 'Producto Viejo', 'is_active' => false]);
        $this->presentationFor($inactiveProduct);

        $activeProduct = Product::factory()->create(['name' => 'Producto Activo']);
        $this->presentationFor($activeProduct, ['is_active' => false]);

        $resultProductInactive = (new ResolveSaleableItemMatches)->execute([
            ['search' => 'Producto Viejo', 'quantity' => 1],
        ])[0];
        $resultPresentationInactive = (new ResolveSaleableItemMatches)->execute([
            ['search' => 'Producto Activo', 'quantity' => 1],
        ])[0];

        $this->assertSame('not_found', $resultProductInactive['status']);
        $this->assertSame('not_found', $resultPresentationInactive['status']);
    }

    public function test_resolves_multiple_requested_items_independently(): void
    {
        $fanta = Product::factory()->create(['name' => 'Fanta Naranja 500ml']);
        $this->presentationFor($fanta, ['price' => 500]);
        $sprite = Product::factory()->create(['name' => 'Sprite 500ml']);
        $this->presentationFor($sprite, ['price' => 450]);

        $results = (new ResolveSaleableItemMatches)->execute([
            ['search' => 'Fanta Naranja', 'quantity' => 2],
            ['search' => 'Sprite', 'quantity' => 5],
        ]);

        $this->assertSame('resolved', $results[0]['status']);
        $this->assertSame(2.0, $results[0]['quantity']);
        $this->assertSame('resolved', $results[1]['status']);
        $this->assertSame(5.0, $results[1]['quantity']);
    }

    public function test_resolves_a_natural_sentence_despite_filler_words_and_unit_notation(): void
    {
        // Mirrors real catalog data: the size lives in the product name itself ("Agua Mineral
        // 2L"), the presentation is just a generic unit — so "de 2 litros" in the user's sentence
        // never appears verbatim anywhere, yet the request should still resolve unambiguously.
        $product = Product::factory()->create(['name' => 'Agua Mineral 2L']);
        $pp = $this->presentationFor($product, ['price' => 800]);

        [$result] = (new ResolveSaleableItemMatches)->execute([
            ['search' => 'agua mineral de 2 litros', 'quantity' => 1],
        ]);

        $this->assertSame('resolved', $result['status']);
        $this->assertSame($pp->uuid, $result['saleable_id']);
    }

    public function test_ranks_by_number_of_matching_words_instead_of_requiring_all(): void
    {
        $exact = Product::factory()->create(['name' => 'Yerba Mate Premium']);
        $this->presentationFor($exact, ['price' => 100]);
        $partial = Product::factory()->create(['name' => 'Mate']);
        $this->presentationFor($partial, ['price' => 50]);

        [$result] = (new ResolveSaleableItemMatches)->execute([
            ['search' => 'Yerba Mate Premium', 'quantity' => 1],
        ]);

        $this->assertSame('resolved', $result['status']);
        $this->assertStringContainsString('Yerba Mate Premium', $result['description']);
    }
}
