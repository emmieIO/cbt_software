<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class TopicDTO
{
    public function __construct(
        public string $subject_id,
        public string $school_class_id,
        public string $name,
        public ?string $description = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            subject_id: $request->string('subject_id'),
            school_class_id: $request->string('school_class_id'),
            name: $request->string('name'),
            description: $request->string('description')
        );
    }

    public function toArray(): array
    {
        return [
            'subject_id' => $this->subject_id,
            'school_class_id' => $this->school_class_id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
