<?php

namespace App\Services;

use App\Models\Question;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkExportService
{
    /**
     * Export the current question bank to an Excel file.
     */
    public function export(): StreamedResponse
    {
        $fileName = 'question_bank_export_'.now()->format('Y_m_d_His').'.xlsx';

        return response()->streamDownload(function () {
            $writer = new Writer;
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues([
                'subject_name',
                'class_name',
                'topic_name',
                'content',
                'explanation',
                'type',
                'difficulty',
                'option_a',
                'option_b',
                'option_c',
                'option_d',
                'correct_option_letter', // A, B, C, or D
                'image_url', // Optional external image URL
            ]));

            // Data
            Question::with(['topic.subject', 'schoolClass', 'options'])->chunk(100, function ($questions) use ($writer) {
                foreach ($questions as $question) {
                    $options = $question->options->values();
                    $correctIndex = $question->options->search(fn ($opt) => $opt->is_correct);
                    $correctLetter = match ($correctIndex) {
                        0 => 'A',
                        1 => 'B',
                        2 => 'C',
                        3 => 'D',
                        default => 'A',
                    };

                    $writer->addRow(Row::fromValues([
                        $question->topic->subject->name,
                        $question->schoolClass->name,
                        $question->topic->name,
                        $question->content,
                        $question->explanation,
                        $question->type->value,
                        $question->difficulty->value,
                        $options[0]?->content ?? '',
                        $options[1]?->content ?? '',
                        $options[2]?->content ?? '',
                        $options[3]?->content ?? '',
                        $correctLetter,
                        $this->resolveImageExportUrl($question->image_path),
                    ]));
                }
            });

            $writer->close();
        }, $fileName);
    }

    /**
     * Download a blank template for importing questions.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $fileName = 'question_import_template.xlsx';

        return response()->streamDownload(function () {
            $writer = new Writer;
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues([
                'topic_name',
                'content',
                'explanation',
                'type',
                'option_a',
                'option_b',
                'option_c',
                'option_d',
                'correct_option_letter',
                'image_url',
            ]));

            // Add a few realistic sample rows so the template remains readable.
            $sampleRows = [
                [
                    'Number Bases',
                    'What is the value of 10 in binary?',
                    '10 in base 10 is equal to 1010 in binary.',
                    'multiple_choice',
                    '1010',
                    '1100',
                    '1111',
                    '1001',
                    'A',
                    '',
                ],
                [
                    'Algebraic Expressions',
                    'Simplify 3x + 2x.',
                    'Like terms are added together to give 5x.',
                    'multiple_choice',
                    '6x',
                    '5x',
                    '5',
                    'x',
                    'B',
                    '',
                ],
                [
                    'Measurement',
                    'Which unit is used to measure mass?',
                    'Mass is commonly measured in kilograms.',
                    'multiple_choice',
                    'Litre',
                    'Metre',
                    'Kilogram',
                    'Second',
                    'C',
                    '',
                ],
            ];

            foreach ($sampleRows as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, $fileName);
    }

    private function resolveImageExportUrl(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        return url('/storage/'.$imagePath);
    }
}
