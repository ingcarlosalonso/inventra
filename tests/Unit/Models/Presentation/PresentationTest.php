<?php

namespace Tests\Unit\Models\Presentation;

use App\Models\Model;
use App\Models\Presentation;
use Tests\Unit\Models\ModelTestCase;

class PresentationTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Presentation::tableName(), [
            'id', 'uuid', 'presentation_type_id', 'name', 'abbreviation', 'is_active',
            'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Presentation());
    }
}
