<?php

namespace Tests\Unit\Actions;

use App\Actions\BuildSaleItemsData;
use App\Enums\DiscountType;
use App\Models\ProductPresentation;
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

    public function test_calculates_simple_item_totals(): void
    {
        $pp = ProductPresentation::factory()->create(['price' => 100]);

        $result = $this->action()->execute(
            [['product_presentation_id' => $pp->uuid, 'description' => 'Test', 'quantity' => 2, 'unit_price' => 100]],
            null,
            null,
        );

        $this->assertEquals(200, $result['subtotal']);
        $this->assertEquals(0, $result['discount_amount']);
        $this->assertEquals(200, $result['total']);
        $this->assertEquals(200, $result['items'][0]['total']);
    }

    public function test_applies_percentage_discount_per_item(): void
    {
        $pp = ProductPresentation::factory()->create();

        $result = $this->action()->execute(
            [[
                'product_presentation_id' => $pp->uuid,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => 200,
                'discount_type' => DiscountType::Percentage->value,
                'discount_value' => 10,
            ]],
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
            [[
                'product_presentation_id' => $pp->uuid,
                'description' => 'Test',
                'quantity' => 1,
                'unit_price' => 200,
                'discount_type' => DiscountType::Fixed->value,
                'discount_value' => 50,
            ]],
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
            [['product_presentation_id' => $pp->uuid, 'description' => 'Test', 'quantity' => 1, 'unit_price' => 100]],
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
            [['product_presentation_id' => $pp->uuid, 'description' => 'Test', 'quantity' => 1, 'unit_price' => 100]],
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
            [['product_presentation_id' => $pp->uuid, 'description' => 'Test', 'quantity' => 1, 'unit_price' => 50]],
            null,
            null,
        );

        $this->assertTrue($result['presentations']->has($pp->uuid));
    }
}
