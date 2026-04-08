<?php

namespace App\DTOs\CashMovementType;

use Illuminate\Http\Request;

readonly class CreateCashMovementTypeDTO
{
    public function __construct(
        public string $name,
        public bool $isIncome,
        public bool $isActive,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            isIncome: $request->boolean('is_income'),
            isActive: $request->boolean('is_active', true),
        );
    }
}
