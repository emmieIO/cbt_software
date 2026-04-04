<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class SchoolDTO
{
    /**
     * @param  string[]  $contact_phone
     */
    public function __construct(
        public string $name,
        public string $type = 'primary',
        public ?string $address = null,
        public ?string $contact_email = null,
        public array $contact_phone = [],
        public bool $is_active = true,
    ) {}

    /**
     * Create a DTO from a request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->validated('name'),
            type: $request->validated('type', 'primary'),
            address: $request->validated('address'),
            contact_email: $request->validated('contact_email'),
            contact_phone: (array) $request->validated('contact_phone', []),
            is_active: (bool) $request->validated('is_active'),
        );
    }

    /**
     * Convert the DTO to an array for model creation/updating.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'is_active' => $this->is_active,
        ];
    }
}
