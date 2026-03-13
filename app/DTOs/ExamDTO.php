<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ExamDTO
{
    /**
     * @param  ExamCompositionDTO[]  $compositions
     */
    public function __construct(
        public string $title,
        public string $academic_session_id,
        public int $duration,
        public string $type,
        public ?string $school_id = null,
        public ?string $subject_id = null,
        public ?string $school_class_id = null,
        public ?string $prospective_class_id = null,
        public ?string $start_time = null,
        public ?string $end_time = null,
        public ?string $description = null,
        public ?string $instructions = null,
        public array $compositions = [],
    ) {}

    public static function fromRequest(Request $request, string $academicSessionId): self
    {
        $compositions = [];
        if ($request->has('compositions')) {
            foreach ($request->array('compositions') as $comp) {
                $compositions[] = ExamCompositionDTO::fromArray($comp);
            }
        }

        return new self(
            title: $request->string('title'),
            academic_session_id: $academicSessionId,
            duration: $request->integer('duration'),
            type: $request->string('type'),
            school_id: $request->input('school_id'),
            subject_id: $request->input('subject_id'),
            school_class_id: $request->input('school_class_id'),
            prospective_class_id: $request->input('prospective_class_id'),
            start_time: $request->input('start_time'),
            end_time: $request->input('end_time'),
            description: $request->input('description'),
            instructions: $request->input('instructions'),
            compositions: $compositions,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'school_id' => $this->school_id,
            'subject_id' => $this->subject_id,
            'school_class_id' => $this->school_class_id,
            'prospective_class_id' => $this->prospective_class_id,
            'academic_session_id' => $this->academic_session_id,
            'duration' => $this->duration,
            'type' => $this->type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'compositions' => $this->compositions,
        ];
    }
}
