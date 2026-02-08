<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Models\Question;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * Create a batch of questions with their options.
     *
     * @param  QuestionDTO[]  $dtos
     */
    public function createQuestionsBatch(array $dtos, string $userId): void
    {
        DB::transaction(function () use ($dtos, $userId) {
            $now = now();

            foreach ($dtos as $dto) {
                // Generate ULID manually for the question to link options
                $questionId = (string) \Illuminate\Support\Str::ulid();

                // Insert the question
                DB::table('questions')->insert([
                    'id' => $questionId,
                    'topic_id' => $dto->topic_id,
                    'school_class_id' => $dto->school_class_id,
                    'content' => $dto->content,
                    'explanation' => $dto->explanation,
                    'type' => $dto->type instanceof \BackedEnum ? $dto->type->value : $dto->type,
                    'difficulty' => $dto->difficulty instanceof \BackedEnum ? $dto->difficulty->value : $dto->difficulty,
                    'is_active' => $dto->is_active,
                    'created_by' => $userId,
                    'version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Prepare and bulk insert options for this question
                $options = array_map(fn ($option) => [
                    'id' => (string) \Illuminate\Support\Str::ulid(),
                    'question_id' => $questionId,
                    'content' => $option->content,
                    'is_correct' => $option->is_correct,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $dto->options);

                if (! empty($options)) {
                    DB::table('options')->insert($options);
                }
            }
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

    /**
     * Get filtered and paginated questions.
     */
    public function getFilteredQuestions(array $filters): LengthAwarePaginator
    {
        return Question::query()
            ->with(['topic.subject', 'schoolClass', 'options'])
            ->filter($filters)
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Delete a single question and all its versions.
     */
    public function deleteQuestion(Question $question): bool
    {
        return DB::transaction(function () use ($question) {
            $parentId = $question->parent_id ?? $question->id;

            return Question::where('id', $parentId)
                ->orWhere('parent_id', $parentId)
                ->delete();
        });
    }

    /**
     * Bulk delete questions.
     */
    public function bulkDeleteQuestions(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            // Get all unique parent IDs for the selected questions
            $parentIds = Question::whereIn('id', $ids)
                ->pluck('parent_id')
                ->filter()
                ->merge($ids)
                ->unique();

            // Delete the selected questions and all their related versions
            return Question::whereIn('id', $ids)
                ->orWhereIn('parent_id', $parentIds)
                ->delete();
        });
    }
}
