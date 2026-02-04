<?php

namespace App\DTOs;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Http\Request;

readonly class QuestionDTO
{
    /**
     * @param  array<int, OptionDTO>  $options
     */
    public function __construct(
        public int $topic_id,
        public int $school_class_id,
        public string $content,
        public ?string $explanation,
        public QuestionType $type,
        public QuestionDifficulty $difficulty,
        public array $options = [],
        public bool $is_active = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            topic_id: $request->integer('topic_id'),
            school_class_id: $request->integer('school_class_id'),
            content: $request->string('content'),
            explanation: $request->string('explanation'),
            type: QuestionType::from($request->string('type')),
            difficulty: QuestionDifficulty::from($request->string('difficulty')),
            is_active: $request->boolean('is_active', true),
            options: array_map(
                fn (array $option) => OptionDTO::fromArray($option),
                $request->array('options')
            ),
        );
    }

    /**
     * Convert DTO to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'topic_id' => $this->topic_id,
            'school_class_id' => $this->school_class_id,
            'content' => $this->content,
            'explanation' => $this->explanation,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'is_active' => $this->is_active,
        ];
    }
}
