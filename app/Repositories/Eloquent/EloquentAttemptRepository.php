<?php

namespace App\Repositories\Eloquent;

use App\Enums\AttemptStatus;
use App\Models\ExamAttempt;
use App\Repositories\Contracts\AttemptRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentAttemptRepository implements AttemptRepositoryInterface
{
    public function findById(string $id): ?ExamAttempt
    {
        return ExamAttempt::with(['exam.subject', 'user'])->find($id);
    }

    public function create(array $data): ExamAttempt
    {
        return ExamAttempt::create($data);
    }

    public function updateMetadata(string $id, array $metadata): bool
    {
        return ExamAttempt::findOrFail($id)->update([
            'metadata' => $metadata,
        ]);
    }

    public function submit(string $id, float $score, ?string $terminationReason = null): bool
    {
        return ExamAttempt::findOrFail($id)->update([
            'status' => AttemptStatus::SUBMITTED,
            'score' => $score,
            'submitted_at' => now(),
            'metadata->termination_reason' => $terminationReason,
        ]);
    }

    public function getSubmittedForExam(string $examId): Collection
    {
        return ExamAttempt::query()
            ->where('exam_id', $examId)
            ->where('status', AttemptStatus::SUBMITTED)
            ->with('user')
            ->orderByDesc('score')
            ->get();
    }

    public function getStudentHistory(string $userId, int $limit = 10): Collection
    {
        return ExamAttempt::query()
            ->where('user_id', $userId)
            ->where('status', AttemptStatus::SUBMITTED)
            ->with(['exam.subject'])
            ->latest('submitted_at')
            ->limit($limit)
            ->get();
    }
}
