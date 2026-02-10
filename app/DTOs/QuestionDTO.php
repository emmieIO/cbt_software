<?php

namespace App\DTOs;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Http\Request;

class QuestionDTO
{
    public string $topic_id;

    public string $school_class_id;

    public string $content;

    public ?string $explanation;

    public QuestionType $type;

    public QuestionDifficulty $difficulty;

    /** @var \App\DTOs\OptionDTO[] */
    public array $options;

    public function __construct(
        string $topic_id,
        string $school_class_id,
        string $content,
        ?string $explanation,
        QuestionType|string $type,
        QuestionDifficulty|string $difficulty,
        array $options = []
    ) {
        $this->topic_id = $topic_id;
        $this->school_class_id = $school_class_id;
        $this->content = $content;
        $this->explanation = $explanation;
        $this->type = is_string($type) ? QuestionType::from($type) : $type;
        $this->difficulty = is_string($difficulty) ? QuestionDifficulty::from($difficulty) : $difficulty;
        $this->options = $options;
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            topic_id: $request->string('topic_id'),
            school_class_id: $request->string('school_class_id'),
            content: $request->string('content'),
            explanation: $request->input('explanation'),
            type: QuestionType::from($request->string('type')),
            difficulty: QuestionDifficulty::from($request->string('difficulty')),
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
        ];
    }
}
