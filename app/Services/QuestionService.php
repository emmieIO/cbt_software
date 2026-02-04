<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    /**
     * Create a new question with its options.
     */
    public function createQuestion(QuestionDTO $dto, string $userId): Question
    {
        return DB::transaction(function () use ($dto, $userId) {
            $question = Question::create([
                ...$dto->toArray(),
                'created_by' => $userId,
            ]);

            foreach ($dto->options as $optionDto) {
                $question->options()->create($optionDto->toArray());
            }

            return $question;
        });
    }

    /**
     * Update an existing question (creating a new version).
     */
    public function updateQuestion(Question $question, QuestionDTO $dto, string $userId): Question
    {
        return DB::transaction(function () use ($question, $dto, $userId) {
            // Deactivate old version
            $question->update(['is_active' => false]);

            // Create new version
            $newVersion = Question::create([
                ...$dto->toArray(),
                'version' => $question->version + 1,
                'parent_id' => $question->parent_id ?? $question->id,
                'created_by' => $userId,
            ]);

            foreach ($dto->options as $optionDto) {
                $newVersion->options()->create($optionDto->toArray());
            }

            return $newVersion;
        });
    }
}
