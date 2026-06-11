<?php

namespace Tests\Unit\Models\PresentationType;

use App\Models\Model;
use App\Models\PresentationType;
use Tests\Unit\Models\ModelTestCase;

class PresentationTypeTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(PresentationType::tableName(), [
            'id', 'uuid', 'name', 'abbreviation', 'is_active', 'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new PresentationType);
    }
}
