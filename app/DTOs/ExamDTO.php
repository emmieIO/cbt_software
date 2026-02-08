<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ExamDTO
{
    public function __construct(
        public string $title,
        public string $subject_id,
        public string $school_class_id,
        public string $academic_session_id,
        public int $duration,
        public string $type,
        public ?string $start_time = null,
        public ?string $end_time = null,
        public ?string $description = null,
        public ?string $instructions = null,
    ) {}

    public static function fromRequest(Request $request, string $academicSessionId): self
    {
        return new self(
            title: $request->string('title'),
            subject_id: $request->string('subject_id'),
            school_class_id: $request->string('school_class_id'),
            academic_session_id: $academicSessionId,
            duration: $request->integer('duration'),
            type: $request->string('type'),
            start_time: $request->string('start_time'),
            end_time: $request->string('end_time'),
            description: $request->string('description'),
            instructions: $request->string('instructions'),
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'subject_id' => $this->subject_id,
            'school_class_id' => $this->school_class_id,
            'academic_session_id' => $this->academic_session_id,
            'duration' => $this->duration,
            'type' => $this->type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'description' => $this->description,
            'instructions' => $this->instructions,
        ];
    }
}
