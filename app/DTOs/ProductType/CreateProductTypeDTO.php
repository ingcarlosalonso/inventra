<?php

namespace App\DTOs\ProductType;

use Illuminate\Http\Request;

readonly class CreateProductTypeDTO
{
    public function __construct(
        public string $name,
        public bool $isActive,
        public ?int $parentId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            isActive: $request->boolean('is_active', true),
            parentId: $request->input('parent_id'),
        );
    }
}
