<?php

namespace Tests\Unit\Models\Presentation;

use App\Models\Presentation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_it_belongs_to_presentation_type(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new Presentation())->presentationType());
    }
}
