<?php

namespace App\DTOs;

use App\Enums\ClassLevel;
use Illuminate\Http\Request;

class SchoolClassDTO
{
    public function __construct(
        public string $name,
        public ClassLevel $level
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name'),
            level: ClassLevel::from($request->string('level'))
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'level' => $this->level,
        ];
    }
}
