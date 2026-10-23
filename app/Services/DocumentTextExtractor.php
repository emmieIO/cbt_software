<?php

namespace App\Services;

interface DocumentTextExtractor
{
    public function supports(string $mimeType, string $extension): bool;

    public function extract(string $path): ExtractedDocumentText;
}
