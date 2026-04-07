<?php

namespace Tests\Unit\Models\PresentationType;

use App\Models\PresentationType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\Unit\Models\ModelTestCase;

class RelationsTest extends ModelTestCase
{
    public function test_it_has_many_presentations(): void
    {
        $this->assertInstanceOf(HasMany::class, (new PresentationType())->presentations());
    }
}
