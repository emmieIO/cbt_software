<?php

namespace App\DTOs;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Http\Request;
use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;

class QuestionDTO
{
    #[SchemaProperty('The database ID of the topic.', required: true)]
    public string $topic_id;

    #[SchemaProperty('The database ID of the school class.', required: true)]
    public string $school_class_id;

    #[SchemaProperty('The main text of the question.', required: true)]
    public string $content;

    #[SchemaProperty('An explanation of why the correct answer is right.', required: true)]
    public ?string $explanation;

    #[SchemaProperty('The type of question.', required: true)]
    public QuestionType $type;

    #[SchemaProperty('The difficulty level.', required: true)]
    public QuestionDifficulty $difficulty;

    #[SchemaProperty('An array of possible answers.', required: true)]
    #[ArrayOf(OptionDTO::class)]
    /** @var \App\DTOs\OptionDTO[] */
    public array $options;

    #[SchemaProperty('Whether the question is currently active.', required: false)]
    public bool $is_active = true;

    public function __construct(
        string $topic_id,
        string $school_class_id,
        string $content,
        ?string $explanation,
        QuestionType|string $type,
        QuestionDifficulty|string $difficulty,
        array $options = [],
        bool $is_active = true
    ) {
        $this->topic_id = $topic_id;
        $this->school_class_id = $school_class_id;
        $this->content = $content;
        $this->explanation = $explanation;
        $this->type = is_string($type) ? QuestionType::from($type) : $type;
        $this->difficulty = is_string($difficulty) ? QuestionDifficulty::from($difficulty) : $difficulty;
        $this->options = $options;
        $this->is_active = $is_active;
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            topic_id: $request->string('topic_id'),
            school_class_id: $request->string('school_class_id'),
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
