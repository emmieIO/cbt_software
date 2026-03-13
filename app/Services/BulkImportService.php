<?php

namespace App\Services;

use App\DTOs\OptionDTO;
use App\DTOs\QuestionDTO;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
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

                    // Header expectation: subject_name, class_name, topic_name, content, explanation, type, difficulty, option_a, option_b, option_c, option_d, correct_option_letter
                    if (count($data) < 12) {
                        continue;
                    }

                    [$subjectName, $className, $topicName, $content, $explanation, $type, $difficulty, $optA, $optB, $optC, $optD, $correctLetter] = $data;

                    // Resolve Subject
                    $subject = Subject::where('name', trim((string) $subjectName))->first();
                    if (! $subject) {
                        throw new \Exception("Subject '{$subjectName}' not found.");
                    }

                    // Resolve Class
                    $class = SchoolClass::where('name', trim((string) $className))->first();
                    if (! $class) {
                        throw new \Exception("Class '{$className}' not found.");
                    }

                    // Resolve Topic (filtered by subject and class)
                    $topic = Topic::where('subject_id', $subject->id)
                        ->where('school_class_id', $class->id)
                        ->where('name', trim((string) $topicName))
                        ->first();

                    if (! $topic) {
                        throw new \Exception("Topic '{$topicName}' for subject '{$subjectName}' and class '{$className}' not found.");
                    }

                    $correctLetter = strtoupper(trim((string) $correctLetter));

                    $optionsData = [
                        ['content' => trim((string) $optA), 'letter' => 'A'],
                        ['content' => trim((string) $optB), 'letter' => 'B'],
                        ['content' => trim((string) $optC), 'letter' => 'C'],
                        ['content' => trim((string) $optD), 'letter' => 'D'],
                    ];

                    $options = array_map(fn ($opt) => new OptionDTO(
                        content: $opt['content'],
                        is_correct: $opt['letter'] === $correctLetter
                    ), $optionsData);

                    $dto = new QuestionDTO(
                        topic_id: $topic->id,
                        school_class_id: $class->id,
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
