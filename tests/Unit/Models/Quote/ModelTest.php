<?php

namespace Tests\Unit\Models\Quote;

use App\Models\Model;
use App\Models\Quote;
use Tests\Unit\Models\ModelTestCase;

class ModelTest extends ModelTestCase
{
    public function test_it_has_expected_columns(): void
    {
        $this->assertHasExpectedColumns(Quote::tableName(), [
            'id', 'uuid', 'client_id', 'user_id', 'currency_id', 'sale_id',
            'subtotal', 'discount_type', 'discount_value', 'discount_amount', 'total',
            'notes', 'starts_at', 'expires_at',
            'created_by', 'updated_by',
            'created_at', 'updated_at', 'deleted_at',
        ]);
    }

    public function test_it_extends_from_custom_model(): void
    {
        $this->assertInstanceOf(Model::class, new Quote);
    }

    public function test_uuid_is_generated_on_create(): void
    {
        $quote = Quote::factory()->create();
        $this->assertNotNull($quote->uuid);
    }

    public function test_is_converted_returns_false_when_not_linked(): void
    {
        $quote = Quote::factory()->create(['sale_id' => null]);
        $this->assertFalse($quote->isConverted());
    }

    public function test_is_converted_returns_true_when_sale_is_set(): void
    {
        $quote = Quote::factory()->converted()->create();
        $this->assertTrue($quote->isConverted());
    }
}
