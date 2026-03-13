<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    /**
     * Get authorized subjects and classes for a user.
     */
    public function getAuthorizedContext(User $user, bool $withTopics = false, bool $strict = false): array
    {
        // Only Super Admins (with sys:manage_settings) see the global context.
        // Examiners (staff) should be scoped to their assigned school level.
        $isGlobalAdmin = $user->can('sys:manage_settings');

        if ($isGlobalAdmin) {
            $subjectsQuery = Subject::query();
            $classesQuery = SchoolClass::query();
            $batchesQuery = \App\Models\ProspectiveClass::where('is_active', true);
        } else {
            $user->loadMissing('school');
            $school = $user->school;

            // Subjects & Classes: Scoped to the school's level (Nursery, Primary, Secondary)
            $subjectsQuery = Subject::query();
            $classesQuery = SchoolClass::query();

            if ($school) {
                $subjectsQuery->where('level', $school->type);
                $classesQuery->where('level', $school->type);
            } else {
                // If not assigned to a school, they see nothing by default
                $subjectsQuery->whereRaw('1 = 0');
                $classesQuery->whereRaw('1 = 0');
            }
            
            // Batches: Entrance exam batches are usually global
            $batchesQuery = \App\Models\ProspectiveClass::where('is_active', true);
        }

        if ($withTopics) {
            $subjectsQuery->with('topics');
        }

        return [
            'subjects' => $subjectsQuery->get(),
            'classes' => $classesQuery->get(),
            'batches' => $batchesQuery->get(),
        ];
    }

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
     * Create multiple questions in a single transaction.
     *
     * @param  QuestionDTO[]  $dtos
     */
    public function createBatchQuestions(array $dtos, string $userId): void
    {
        DB::transaction(function () use ($dtos, $userId) {
            foreach ($dtos as $dto) {
                $question = Question::create([
                    ...$dto->toArray(),
                    'created_by' => $userId,
                ]);

                foreach ($dto->options as $optionDto) {
                    $question->options()->create($optionDto->toArray());
                }
            }
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
                    'image_path' => $dto->image_path,
                    'explanation' => $dto->explanation,
                    'type' => $dto->type instanceof \BackedEnum ? $dto->type->value : $dto->type,
                    'difficulty' => $dto->difficulty instanceof \BackedEnum ? $dto->difficulty->value : $dto->difficulty,
                    'created_by' => $userId,
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
     * Update an existing question.
     */
    public function updateQuestion(Question $question, QuestionDTO $dto, string $userId): Question
    {
        return DB::transaction(function () use ($question, $dto) {
            $question->update($dto->toArray());

            $existingOptions = $question->options;

            // Standard CBT Pattern: If the number of options is the same,
            // update them in place to keep the IDs (and student sessions) stable.
            if ($existingOptions->count() === count($dto->options)) {
                foreach ($existingOptions as $index => $option) {
                    $option->update([
                        'content' => $dto->options[$index]->content,
                        'is_correct' => $dto->options[$index]->is_correct,
                    ]);
                }
            } else {
                // If the count changed, we have to recreate.
                // Note: This is rare but will invalidate active shuffles for this specific question.
                $question->options()->delete();
                foreach ($dto->options as $optionDto) {
                    $question->options()->create($optionDto->toArray());
                }
            }

            return $question;
        });
    }

    /**
     * Get filtered and paginated questions.
     */
    public function getFilteredQuestions(array $filters, User $user): LengthAwarePaginator
    {
        $query = Question::query()
            ->with(['topic.subject', 'schoolClass', 'prospectiveClass', 'options', 'creator']);

        // Scope to teacher's school level if they aren't a global admin
        if (! $user->can('sys:manage_settings')) {
            $user->loadMissing('school');
            $school = $user->school;
            
            if ($school) {
                $query->whereHas('schoolClass', function ($q) use ($school) {
                    $q->where('level', $school->type);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->filter($filters)
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Delete a single question.
     */
    public function deleteQuestion(Question $question): bool
    {
        return (bool) $question->delete();
    }

    /**
     * Bulk delete questions.
     */
    public function bulkDeleteQuestions(array $ids, User $user): int
    {
        // Filter IDs to only those the user is authorized to delete
        $authorizedIds = Question::query()
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn ($question) => $user->can('delete', $question))
            ->pluck('id')
            ->toArray();

        if (empty($authorizedIds)) {
            return 0;
        }

        return Question::query()->whereIn('id', $authorizedIds)->delete();
    }
}
