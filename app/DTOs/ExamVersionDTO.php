<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ExamVersionDTO
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
