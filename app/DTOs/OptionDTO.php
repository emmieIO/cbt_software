<?php

namespace App\DTOs;

use NeuronAI\StructuredOutput\SchemaProperty;

class OptionDTO
{
    #[SchemaProperty('The textual content of the option.', required: true)]
    public string $content;

    #[SchemaProperty('Whether this option is the correct answer.', required: true)]
    public bool $is_correct;

    public function __construct(string $content, bool|string $is_correct = false)
    {
        $this->content = $content;
        $this->is_correct = is_string($is_correct) ? filter_var($is_correct, FILTER_VALIDATE_BOOLEAN) : $is_correct;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            is_correct: $data['is_correct'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'is_correct' => $this->is_correct,
        ];
    }
}
