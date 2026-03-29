<?php

namespace App\Repositories\Contracts;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExamRepositoryInterface
{
    /**
     * Get paginated exams with filters.
     */
    public function getPaginatedExams(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find an exam by ID.
     */
    public function findById(string $id): ?Exam;

    /**
     * Assign a specific student to an exam.
     */
    public function assignStudent(string $examId, string $userId): void;

    /**
     * Remove a student's assignment from an exam.
     */
    public function removeStudent(string $examId, string $userId): void;

    /**
     * Get all students assigned to a specific exam.
     */
    public function getAssignedStudents(string $examId): Collection;

    /**
     * Get exams available for a specific student.
     */
    public function getExamsForStudent(string $userId): Collection;
}
