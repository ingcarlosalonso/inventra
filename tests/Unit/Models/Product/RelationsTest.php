<?php

namespace Tests\Unit\Models\Product;

use App\Models\Barcode;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\ProductType;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_product_type(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(ProductType::class, $product->productType);
    }

    public function test_has_many_barcodes(): void
    {
        $product = Product::factory()->create();
        Barcode::factory()->count(2)->create(['product_id' => $product->id]);

        $this->assertCount(2, $product->barcodes);
    }

    public function test_belongs_to_currency_nullable(): void
    {
        $product = Product::factory()->create(['currency_id' => null]);

        $this->assertNull($product->currency);
    }

    public function test_belongs_to_currency(): void
    {
        $currency = Currency::factory()->create();
        $product = Product::factory()->create(['currency_id' => $currency->id]);

        $this->assertInstanceOf(Currency::class, $product->currency);
    }

    public function test_has_many_product_presentations(): void
    {
        $product = Product::factory()->create();
        ProductPresentation::factory()->count(2)->create(['product_id' => $product->id]);

        $this->assertCount(2, $product->productPresentations);
    }
}
