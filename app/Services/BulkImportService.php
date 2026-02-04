<?php

namespace App\Services;

use App\DTOs\OptionDTO;
use App\DTOs\QuestionDTO;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Topic;
use App\Models\SchoolClass;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkImportService
{
    public function __construct(protected QuestionService $questionService) {}

    /**
     * Import questions from a CSV file.
     *
     * @throws ValidationException
     */
    public function importFromCsv(UploadedFile $file, string $userId): int
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle); // Assume first row is header

        // Simple validation of headers (Subject, Topic, Class, Content, Difficulty, Type, Option1, Option2, Option3, Option4, CorrectOptionIndex)
        // We can make this more robust later.

        $importedCount = 0;
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                // Map CSV columns to data
                // Header expectation: topic_id, school_class_id, content, explanation, type, difficulty, options_csv (pipe separated), correct_option_index
                [$topicId, $classId, $content, $explanation, $type, $difficulty, $optionsStr, $correctIndex] = $row;

                $options = array_map(fn($opt, $index) => new OptionDTO(
                    content: trim($opt),
                    is_correct: (int)$index === (int)$correctIndex
                ), explode('|', $optionsStr), array_keys(explode('|', $optionsStr)));

                $dto = new QuestionDTO(
                    topic_id: trim($topicId),
                    school_class_id: trim($classId),
                    content: trim($content),
                    explanation: trim($explanation),
                    type: QuestionType::from(trim($type)),
                    difficulty: QuestionDifficulty::from(trim($difficulty)),
                    options: $options
                );

                $this->questionService->createQuestion($dto, $userId);
                $importedCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'file' => ["Error at row $rowNumber: " . $e->getMessage()],
            ]);
        } finally {
            fclose($handle);
        }

        return $importedCount;
    }
}
