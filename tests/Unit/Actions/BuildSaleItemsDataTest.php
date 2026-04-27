<?php

namespace Tests\Unit\Actions;

use App\Actions\BuildSaleItemsData;
use App\Enums\DiscountType;
use App\Enums\SaleItemType;
use App\Models\CompositeProduct;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuildSaleItemsDataTest extends TestCase
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

    private function action(): BuildSaleItemsData
    {
        return new BuildSaleItemsData;
    }

    private function productItem(ProductPresentation $pp, array $extra = []): array
    {
        return array_merge([
            'item_type' => SaleItemType::Product->value,
            'saleable_id' => $pp->uuid,
            'description' => 'Test',
            'quantity' => 1,
            'unit_price' => 100,
        ], $extra);
    }

    // ─── Product items ────────────────────────────────────────────────────────

    public function test_calculates_simple_item_totals(): void
    {
        $pp = ProductPresentation::factory()->create(['price' => 100]);

        $result = $this->action()->execute(
            [$this->productItem($pp, ['quantity' => 2, 'unit_price' => 100])],
            null,
            null,
        );

        $this->assertEquals(200, $result['subtotal']);
        $this->assertEquals(0, $result['discount_amount']);
        $this->assertEquals(200, $result['total']);
        $this->assertEquals(200, $result['items'][0]['total']);
    }

    public function test_product_item_sets_saleable_and_presentation_id(): void
    {
        $pp = ProductPresentation::factory()->create();

        $result = $this->action()->execute(
            [$this->productItem($pp)],
            null,
            null,
        );

        $this->assertEquals('product_presentation', $result['items'][0]['saleable_type']);
        $this->assertEquals($pp->id, $result['items'][0]['saleable_id']);
        $this->assertEquals($pp->id, $result['items'][0]['product_presentation_id']);
    }

    public function test_applies_percentage_discount_per_item(): void
    {
        $pp = ProductPresentation::factory()->create();

        $result = $this->action()->execute(
            [$this->productItem($pp, ['unit_price' => 200, 'discount_type' => DiscountType::Percentage->value, 'discount_value' => 10])],
            null,
            null,
        );

        $this->assertEquals(20, $result['items'][0]['discount_amount']);
        $this->assertEquals(180, $result['items'][0]['total']);
        $this->assertEquals(180, $result['subtotal']);
    }

    public function test_applies_fixed_discount_per_item(): void
    {
        $pp = ProductPresentation::factory()->create();

        $result = $this->action()->execute(
            [$this->productItem($pp, ['unit_price' => 200, 'discount_type' => DiscountType::Fixed->value, 'discount_value' => 50])],
            null,
            null,
        );

        $this->assertEquals(50, $result['items'][0]['discount_amount']);
        $this->assertEquals(150, $result['items'][0]['total']);
    }

    public function test_applies_sale_level_percentage_discount(): void
    {
        $pp = ProductPresentation::factory()->create();

        $result = $this->action()->execute(
            [$this->productItem($pp, ['unit_price' => 100])],
            DiscountType::Percentage->value,
            20,
        );

        $this->assertEquals(100, $result['subtotal']);
        $this->assertEquals(20, $result['discount_amount']);
        $this->assertEquals(80, $result['total']);
    }

    public function test_applies_sale_level_fixed_discount(): void
    {
        $pp = ProductPresentation::factory()->create();

        $result = $this->action()->execute(
            [$this->productItem($pp, ['unit_price' => 100])],
            DiscountType::Fixed->value,
            30,
        );

        $this->assertEquals(30, $result['discount_amount']);
        $this->assertEquals(70, $result['total']);
    }

    public function test_returns_presentations_keyed_by_uuid(): void
    {
        $pp = ProductPresentation::factory()->create();

        $result = $this->action()->execute(
            [$this->productItem($pp)],
            null,
            null,
        );

        $this->assertTrue($result['presentations']->has($pp->uuid));
    }

    // ─── Composite items ──────────────────────────────────────────────────────

    public function test_composite_item_sets_saleable_and_nulls_presentation_id(): void
    {
        $composite = CompositeProduct::factory()->create();

        $result = $this->action()->execute(
            [[
                'item_type' => SaleItemType::Composite->value,
                'saleable_id' => $composite->uuid,
                'description' => 'Kit',
                'quantity' => 1,
                'unit_price' => 500,
            ]],
            null,
            null,
        );

        $this->assertEquals('composite_product', $result['items'][0]['saleable_type']);
        $this->assertEquals($composite->id, $result['items'][0]['saleable_id']);
        $this->assertNull($result['items'][0]['product_presentation_id']);
    }

    public function test_composite_item_calculates_total(): void
    {
        $composite = CompositeProduct::factory()->create();

        $result = $this->action()->execute(
            [[
                'item_type' => SaleItemType::Composite->value,
                'saleable_id' => $composite->uuid,
                'description' => 'Kit',
                'quantity' => 2,
                'unit_price' => 300,
            ]],
            null,
            null,
        );

        $this->assertEquals(600, $result['subtotal']);
        $this->assertEquals(600, $result['items'][0]['total']);
    }

    public function test_returns_composites_keyed_by_uuid(): void
    {
        $composite = CompositeProduct::factory()->create();

        $result = $this->action()->execute(
            [[
                'item_type' => SaleItemType::Composite->value,
                'saleable_id' => $composite->uuid,
                'description' => 'Kit',
                'quantity' => 1,
                'unit_price' => 100,
            ]],
            null,
            null,
        );

        $this->assertTrue($result['composites']->has($composite->uuid));
    }

    // ─── Promotion items ──────────────────────────────────────────────────────

    public function test_promotion_item_sets_saleable_and_nulls_presentation_id(): void
    {
        $promotion = Promotion::factory()->create();

        $result = $this->action()->execute(
            [[
                'item_type' => SaleItemType::Promotion->value,
                'saleable_id' => $promotion->uuid,
                'description' => 'Promo',
                'quantity' => 1,
                'unit_price' => 200,
            ]],
            null,
            null,
        );

        $this->assertEquals('promotion', $result['items'][0]['saleable_type']);
        $this->assertEquals($promotion->id, $result['items'][0]['saleable_id']);
        $this->assertNull($result['items'][0]['product_presentation_id']);
    }

    public function test_returns_promotions_keyed_by_uuid(): void
    {
        $promotion = Promotion::factory()->create();

        $result = $this->action()->execute(
            [[
                'item_type' => SaleItemType::Promotion->value,
                'saleable_id' => $promotion->uuid,
                'description' => 'Promo',
                'quantity' => 1,
                'unit_price' => 100,
            ]],
            null,
            null,
        );

        $this->assertTrue($result['promotions']->has($promotion->uuid));
    }

    // ─── Mixed items ──────────────────────────────────────────────────────────

    public function test_mixed_items_calculate_combined_subtotal(): void
    {
        $pp = ProductPresentation::factory()->create();
        $composite = CompositeProduct::factory()->create();

        $result = $this->action()->execute(
            [
                $this->productItem($pp, ['quantity' => 1, 'unit_price' => 100]),
                [
                    'item_type' => SaleItemType::Composite->value,
                    'saleable_id' => $composite->uuid,
                    'description' => 'Kit',
                    'quantity' => 2,
                    'unit_price' => 150,
                ],
            ],
            null,
            null,
        );

        $this->assertEquals(400, $result['subtotal']); // 100 + 300
        $this->assertEquals(400, $result['total']);
    }
}
