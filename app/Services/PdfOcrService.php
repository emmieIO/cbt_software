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
                    $pages[] = '--- Page '.($index + 1)." ---\n".$text;
                }
            }

            if ($pages === []) {
                throw new RuntimeException('OCR could not find readable text in this PDF.');
            }

            return implode("\n\n", $pages);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    /**
     * @return array<int, string>
     */
    private function renderPages(string $pdfPath, string $directory): array
    {
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
                    (string) config('services.pdf_ocr.max_pages', 25),
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
