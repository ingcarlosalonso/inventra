<?php

namespace Tests\Unit\Models\ProductType;

use App\Models\ProductType;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_belongs_to_parent(): void
    {
        $parent = ProductType::factory()->create();
        $child = ProductType::factory()->childOf($parent)->create();

        $this->assertInstanceOf(ProductType::class, $child->parent);
        $this->assertTrue($child->parent->is($parent));
    }

    public function test_has_many_children(): void
    {
        $parent = ProductType::factory()->create();
        ProductType::factory()->count(3)->childOf($parent)->create();

        $this->assertCount(3, $parent->children);
    }

    public function test_children_is_empty_when_root(): void
    {
        $type = ProductType::factory()->create();

        $this->assertCount(0, $type->children);
    }
}
