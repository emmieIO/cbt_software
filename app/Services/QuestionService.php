<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Models\Question;
use App\Models\User;
use App\Repositories\Contracts\AcademicRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuestionService
{
    public function __construct(
        protected QuestionRepositoryInterface $questionRepo,
        protected AcademicRepositoryInterface $academicRepo,
        protected UserRepositoryInterface $userRepo
    ) {}

    public function getAuthorizedContext(User $user, bool $withTopics = false): array
    {
        return [
            'subjects' => $this->academicRepo->getAllSubjects($withTopics),
            'classes' => $this->academicRepo->getClasses(),
        ];
    }

    /**
     * Create a new question with its options.
     */
    public function createQuestion(QuestionDTO $dto, string $userId): Question
    {
        $data = $dto->toArray();
        $data['created_by'] = $userId;

        $options = array_map(fn ($opt) => $opt->toArray(), $dto->options);

        return $this->questionRepo->create($data, $options);
    }

    /**
     * Create multiple questions in a single transaction.
     *
     * @param  QuestionDTO[]  $dtos
     */
    public function createBatchQuestions(array $dtos, string $userId): void
    {
        $questionsData = [];
        foreach ($dtos as $dto) {
            $qData = $dto->toArray();
            $qData['created_by'] = $userId;
            $qData['options'] = array_map(fn ($opt) => $opt->toArray(), $dto->options);
            $questionsData[] = $qData;
        }

        $this->questionRepo->bulkCreate($questionsData);
    }

    /**
     * Update an existing question.
     */
    public function updateQuestion(Question $question, QuestionDTO $dto, string $userId): Question
    {
        $data = $dto->toArray();
        $options = array_map(fn ($opt) => $opt->toArray(), $dto->options);

        return $this->questionRepo->update($question->id, $data, $options);
    }

    /**
     * Get filtered and paginated questions.
     */
    public function getFilteredQuestions(array $filters, User $user): LengthAwarePaginator
    {
        return $this->questionRepo->getPaginatedQuestions(10, $filters);
    }

    /**
     * Delete a single question.
     */
    public function deleteQuestion(Question $question): bool
    {
        return $this->questionRepo->delete($question->id);
    }

    /**
     * Bulk delete questions.
     */
    public function bulkDeleteQuestions(array $ids, User $user): int
    {
        $count = 0;
        foreach ($ids as $id) {
            $question = $this->questionRepo->findById($id);
            if ($question && $user->can('delete', $question)) {
                $this->questionRepo->delete($id);
                $count++;
            }
        }

        return $count;
    }
}
