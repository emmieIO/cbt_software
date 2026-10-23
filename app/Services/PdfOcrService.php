<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PdfOcrService
{
    /**
     * Extract text from an image-only PDF using Tesseract OCR.
     */
    public function extract(string $pdfPath): string
    {
        return collect($this->extractPages($pdfPath))
            ->map(fn (array $page): string => '--- Page '.$page['number']." ---\n".$page['text'])
            ->implode("\n\n");
    }

    /**
     * @return array<int, array{number: int, text: string}>
     */
    public function extractPages(string $pdfPath): array
    {
        if (! config('services.pdf_ocr.enabled', true)) {
            throw new RuntimeException('No readable text was found in this PDF. OCR is disabled.');
        }

        $directory = storage_path('app/private/tmp/pdf-ocr/'.Str::uuid());
        File::ensureDirectoryExists($directory);

        try {
            $images = $this->renderPages($pdfPath, $directory);
            $pages = [];

            foreach ($images as $index => $image) {
                $text = $this->recognizePage($image);

                if ($text !== '') {
                    $pages[] = ['number' => $index + 1, 'text' => $text];
                }
            }

            if ($pages === []) {
                throw new RuntimeException('OCR could not find readable text in this PDF.');
            }

            return $pages;
        } finally {
            File::deleteDirectory($directory);
        }
    }

    /**
     * @return array<int, string>
     */
    private function renderPages(string $pdfPath, string $directory): array
    {
        $configuredMaxPages = config('services.pdf_ocr.max_pages', 25);
        $maxPages = is_int($configuredMaxPages) || is_string($configuredMaxPages)
            ? filter_var($configuredMaxPages, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => PHP_INT_MAX - 1]])
            : false;

        if ($maxPages === false) {
            throw new RuntimeException('PDF_OCR_MAX_PAGES must be a positive integer less than PHP_INT_MAX.');
        }

        try {
            $result = Process::timeout((int) config('services.pdf_ocr.render_timeout', 180))
                ->run([
                    (string) config('services.pdf_ocr.pdftoppm_binary', 'pdftoppm'),
                    '-png',
                    '-r',
                    (string) config('services.pdf_ocr.dpi', 250),
                    '-f',
                    '1',
                    '-l',
                    (string) ($maxPages + 1),
                    $pdfPath,
                    $directory.'/page',
                ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('OCR could not start the PDF page converter. Check that pdftoppm is installed.', previous: $exception);
        }

        if ($result->failed()) {
            throw new RuntimeException('OCR could not convert the PDF pages: '.trim($result->errorOutput()));
        }

        $images = File::glob($directory.'/page-*.png');
        natsort($images);

        if ($images === []) {
            throw new RuntimeException('OCR could not render any pages from this PDF.');
        }

        if (count($images) > $maxPages) {
            throw new RuntimeException("This PDF exceeds the OCR limit of {$maxPages} pages. Split the PDF into smaller documents and try again.");
        }

        return array_values($images);
    }

    private function recognizePage(string $imagePath): string
    {
        try {
            $result = Process::timeout((int) config('services.pdf_ocr.page_timeout', 60))
                ->run([
                    (string) config('services.pdf_ocr.tesseract_binary', 'tesseract'),
                    $imagePath,
                    'stdout',
                    '-l',
                    (string) config('services.pdf_ocr.language', 'eng'),
                    '--oem',
                    '1',
                    '--psm',
                    '3',
                    '-c',
                    'preserve_interword_spaces=1',
                ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('OCR could not start Tesseract. Check that Tesseract OCR is installed.', previous: $exception);
        }

        if ($result->failed()) {
            throw new RuntimeException('Tesseract OCR failed: '.trim($result->errorOutput()));
        }

        return trim($result->output());
    }
}
