<?php

namespace Tests\Unit\Models\Presentation;

use App\Models\Model;
use App\Models\Presentation;
use App\Models\PresentationType;
use Tests\Unit\Models\ModelTestCase;

class PresentationTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Presentation::tableName(), [
            'id', 'uuid', 'presentation_type_id', 'quantity', 'is_active',
            'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Presentation);
    }

    public function test_display_combines_quantity_and_type_abbreviation(): void
    {
        $type = PresentationType::factory()->create(['abbreviation' => 'L']);
        $presentation = Presentation::factory()->create(['presentation_type_id' => $type->id, 'quantity' => 2]);

        $this->assertSame('2 L', $presentation->display);
    }
}
