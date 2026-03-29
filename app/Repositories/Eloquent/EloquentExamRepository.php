<?php

namespace App\Repositories\Eloquent;

use App\Models\Exam;
use App\Models\User;
use App\Repositories\Contracts\ExamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentExamRepository implements ExamRepositoryInterface
{
    public function getPaginatedExams(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return Exam::query()
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['school_class_id'] ?? null, fn ($q, $classId) => $q->where('school_class_id', $classId))
            ->with(['subject', 'schoolClass'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(string $id): ?Exam
    {
        return Exam::with(['subject', 'schoolClass'])->find($id);
    }

    public function assignStudent(string $examId, string $userId): void
    {
        $exam = Exam::findOrFail($examId);
        $exam->users()->syncWithoutDetaching([$userId]);
    }

    public function removeStudent(string $examId, string $userId): void
    {
        $exam = Exam::findOrFail($examId);
        $exam->users()->detach($userId);
    }

    public function getAssignedStudents(string $examId): Collection
    {
        return Exam::findOrFail($examId)->users;
    }

    public function getExamsForStudent(string $userId): Collection
    {
        return User::findOrFail($userId)
            ->assignedExams() // We'll need to define this relationship
            ->where('status', 'live')
            ->get();
    }
}
