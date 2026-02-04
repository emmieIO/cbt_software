<?php

namespace App\DTOs;

readonly class OptionDTO
{
    public function __construct(
        public string $content,
        public bool $is_correct = false,
    ) {}

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
