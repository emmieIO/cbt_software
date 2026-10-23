<?php

namespace App\Services;

use RuntimeException;
use Spatie\PdfToText\Pdf;

class PdfTextExtractor implements DocumentTextExtractor
{
    public function __construct(private readonly PdfOcrService $pdfOcrService) {}

    public function supports(string $mimeType, string $extension): bool
    {
        return $mimeType === 'application/pdf' || strtolower($extension) === 'pdf';
    }

    public function extract(string $path): ExtractedDocumentText
    {
        $text = Pdf::getText($path, options: ['layout']);

        if ($this->readableCharacterCount($text) < (int) config('services.pdf_ocr.minimum_text_characters', 10)) {
            $pages = $this->pdfOcrService->extractPages($path);

            return new ExtractedDocumentText(
                text: $this->joinPages($pages),
                pages: $pages,
                method: 'ocr',
            );
        }

        $pages = $this->splitPages($text);

        if ($pages === []) {
            throw new RuntimeException('No readable text was found in this PDF.');
        }

        return new ExtractedDocumentText(
            text: $text,
            pages: $pages,
            method: 'pdf_text',
        );
    }

    private function readableCharacterCount(string $text): int
    {
        return mb_strlen(preg_replace('/[\s\p{P}\p{S}]+/u', '', $text) ?? '');
    }

    /** @return array<int, array{number: int, text: string}> */
    private function splitPages(string $text): array
    {
        $rawPages = preg_split('/\f/u', str_replace("\r\n", "\n", $text)) ?: [];
        $pages = [];

        foreach ($rawPages as $index => $pageText) {
            $pageText = trim($pageText);

            if ($pageText !== '') {
                $pages[] = ['number' => $index + 1, 'text' => $pageText];
            }
        }

        return $pages;
    }

    /**
     * @param  array<int, array{number: int, text: string}>  $pages
     */
    private function joinPages(array $pages): string
    {
        return collect($pages)
            ->map(fn (array $page): string => '--- Page '.$page['number']." ---\n".$page['text'])
            ->implode("\n\n");
    }
}
