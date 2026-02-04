<?php

namespace App\Services;

use App\DTOs\OptionDTO;
use App\DTOs\QuestionDTO;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class BulkImportService
{
    public function __construct(protected QuestionService $questionService) {}

    /**
     * Import questions from a CSV or Excel file.
     *
     * @throws ValidationException
     */
    public function import(UploadedFile $file, string $userId): int
    {
        $extension = $file->getClientOriginalExtension();

        $reader = match ($extension) {
            'csv' => new CsvReader,
            'xlsx' => new XlsxReader,
            default => throw ValidationException::withMessages(['file' => ['Unsupported file format. Please upload a .csv or .xlsx file.']]),
        };

        $reader->open($file->getRealPath());

        $importedCount = 0;
        $rowNumber = 0;

        DB::beginTransaction();
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;

                    // Skip header row
                    if ($rowNumber === 1) {
                        continue;
                    }

                    $data = $row->toArray();

                    // Header expectation: topic_id, school_class_id, content, explanation, type, difficulty, options_csv (pipe separated), correct_option_index
                    // OpenSpout returns an array of cells.
                    if (count($data) < 8) {
                        continue;
                    } // Basic skip for empty or invalid rows

                    [$topicId, $classId, $content, $explanation, $type, $difficulty, $optionsStr, $correctIndex] = $data;

                    $options = array_map(fn ($opt, $index) => new OptionDTO(
                        content: trim((string) $opt),
                        is_correct: (int) $index === (int) $correctIndex
                    ), explode('|', (string) $optionsStr), array_keys(explode('|', (string) $optionsStr)));

                    $dto = new QuestionDTO(
                        topic_id: trim((string) $topicId),
                        school_class_id: trim((string) $classId),
                        content: trim((string) $content),
                        explanation: trim((string) $explanation),
                        type: QuestionType::from(trim((string) $type)),
                        difficulty: QuestionDifficulty::from(trim((string) $difficulty)),
                        options: $options
                    );

                    $this->questionService->createQuestion($dto, $userId);
                    $importedCount++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'file' => ["Error at row $rowNumber: ".$e->getMessage()],
            ]);
        } finally {
            $reader->close();
        }

        return $importedCount;
    }
}
