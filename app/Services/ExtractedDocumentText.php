<?php

namespace App\Services;

final readonly class ExtractedDocumentText
{
    /**
     * @param  array<int, array{number: int, text: string}>  $pages
     */
    public function __construct(
        public string $text,
        public array $pages,
        public string $method,
    ) {}
}
