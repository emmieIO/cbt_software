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
    public function getAuthorizedContext(User $user, bool $withTopics = false): array
    {
        $isAdmin = $user->hasRole('admin');

        $subjectsQuery = $isAdmin
            ? Subject::query()
            : Subject::whereIn('id', $user->currentAssignments()->pluck('subject_id'));

        if ($withTopics) {
            $subjectsQuery->with('topics');
        }

        $classesQuery = $isAdmin
            ? SchoolClass::query()
            : SchoolClass::whereIn('id', $user->currentAssignments()->pluck('school_class_id'));

        return [
            'subjects' => $subjectsQuery->get(),
            'classes' => $classesQuery->get(),
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
            ->with(['topic.subject', 'schoolClass', 'options']);

        // Scope to teacher's assignments if they aren't an admin
        if (! $user->hasRole('admin')) {
            $assignments = $user->currentAssignments();

            $query->where(function ($q) use ($assignments) {
                $q->whereIn('school_class_id', $assignments->pluck('school_class_id')->toArray())
                    ->whereHas('topic', function ($q) use ($assignments) {
                        $q->whereIn('subject_id', $assignments->pluck('subject_id')->toArray());
                    });
            });
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
