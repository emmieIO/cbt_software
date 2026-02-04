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
                'topic_id',
                'school_class_id',
                'content',
                'explanation',
                'type',
                'difficulty',
                'options_csv',
                'correct_option_index',
            ]));

            // Data
            Question::with(['options'])->chunk(100, function ($questions) use ($writer) {
                foreach ($questions as $question) {
                    $optionsStr = $question->options->pluck('content')->implode('|');
                    $correctIndex = $question->options->search(fn ($opt) => $opt->is_correct);

                    $writer->addRow(Row::fromValues([
                        $question->topic_id,
                        $question->school_class_id,
                        $question->content,
                        $question->explanation,
                        $question->type->value,
                        $question->difficulty->value,
                        $optionsStr,
                        $correctIndex !== false ? $correctIndex : 0,
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
                'topic_id',
                'school_class_id',
                'content',
                'explanation',
                'type',
                'difficulty',
                'options_csv',
                'correct_option_index',
            ]));

            // Add one example row
            $writer->addRow(Row::fromValues([
                'ULID_OF_TOPIC',
                'ULID_OF_CLASS',
                'What is the capital of Nigeria?',
                'Abuja is the federal capital territory.',
                'multiple_choice',
                'easy',
                'Lagos|Abuja|Kano|Ibadan',
                '1',
            ]));

            $writer->close();
        }, $fileName);
    }
}
