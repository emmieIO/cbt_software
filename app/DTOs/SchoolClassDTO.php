<?php

namespace App\DTOs;

use App\Enums\ClassLevel;
use Illuminate\Http\Request;

class SchoolClassDTO
{
    public function __construct(
        public string $name,
        public ClassLevel $level,
        public string $school_id
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name'),
            level: ClassLevel::from($request->string('level')),
            school_id: $request->string('school_id')
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'level' => $this->level,
            'school_id' => $this->school_id,
        ];
    }
}
