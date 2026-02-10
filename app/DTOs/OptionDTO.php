<?php

namespace App\DTOs;

class OptionDTO
{
    public string $content;

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
