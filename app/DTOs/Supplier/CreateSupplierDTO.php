<?php

namespace App\DTOs\Supplier;

use Illuminate\Http\Request;

readonly class CreateSupplierDTO
{
    public function __construct(
        public string $name,
        public ?string $contactName,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $notes,
        public bool $isActive,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            contactName: $request->input('contact_name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            address: $request->input('address'),
            notes: $request->input('notes'),
            isActive: $request->boolean('is_active', true),
        );
    }
}
