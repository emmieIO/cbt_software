<?php

namespace App\Repositories\Contracts;

use App\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Collection;

interface AttemptRepositoryInterface
{
    /**
     * Find an attempt by ID with related exam and questions.
     */
    public function findById(string $id): ?ExamAttempt;

    /**
     * Create a new exam attempt for a user.
     */
    public function create(array $data): ExamAttempt;

    /**
     * Update attempt metadata (saved answers, violations, etc.).
     */
    public function updateMetadata(string $id, array $metadata): bool;

    /**
     * Submit an attempt and record final score.
     */
    public function submit(string $id, float $score, ?string $terminationReason = null): bool;

    /**
     * Get all submitted attempts for an exam.
     */
    public function getSubmittedForExam(string $examId): Collection;

    /**
     * Get recent attempts for a specific student.
     */
    public function getStudentHistory(string $userId, int $limit = 10): Collection;
}
