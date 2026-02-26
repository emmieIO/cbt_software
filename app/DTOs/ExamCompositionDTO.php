<?php

namespace App\DTOs;

class ExamCompositionDTO
{
    public function __construct(
        public string $subject_id,
        public int $question_count,
        public ?string $topic_id = null,
        public ?string $source_class_id = null,
        public float $marks_per_question = 1.00,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject_id: $data['subject_id'],
            question_count: (int) $data['question_count'],
            topic_id: $data['topic_id'] ?? null,
            source_class_id: $data['source_class_id'] ?? null,
            marks_per_question: (float) ($data['marks_per_question'] ?? 1.00),
        );
    }

    public function toArray(): array
    {
        return [
            'subject_id' => $this->subject_id,
            'topic_id' => $this->topic_id,
            'source_class_id' => $this->source_class_id,
            'question_count' => $this->question_count,
            'marks_per_question' => $this->marks_per_question,
        ];
    }
}
