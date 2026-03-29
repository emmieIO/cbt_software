<?php

namespace App\Repositories\Contracts;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface QuestionRepositoryInterface
{
    /**
     * Get paginated questions with advanced filtering.
     */
    public function getPaginatedQuestions(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find a question by ID with its options and topic.
     */
    public function findById(string $id): ?Question;

    /**
     * Create a new question and its options.
     */
    public function create(array $data, array $options): Question;

    /**
     * Update an existing question and its options.
     */
    public function update(string $id, array $data, array $options = []): Question;

    /**
     * Delete a question.
     */
    public function delete(string $id): bool;

    /**
     * Get questions by topic and difficulty for exam composition.
     */
    public function getForComposition(string $topicId, string $difficulty, int $count): Collection;

    /**
     * Bulk create questions (used by AI Lab/Import).
     */
    public function bulkCreate(array $questions): int;
}
