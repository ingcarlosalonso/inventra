<?php

namespace Tests\Unit\Models\ProductType;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_it_belongs_to_parent(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new ProductType())->parent());
    }

    public function test_it_has_many_children(): void
    {
        $this->assertInstanceOf(HasMany::class, (new ProductType())->children());
    }
}
