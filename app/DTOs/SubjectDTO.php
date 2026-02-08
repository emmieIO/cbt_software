<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class SubjectDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name'),
            description: $request->string('description')
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
