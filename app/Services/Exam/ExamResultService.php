<?php

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ExamResultService
{
    /**
     * Build the scoped query for result listing.
     */
    public function queryVisibleResults(User $user, ?string $schoolId = null): Builder
    {
        $query = Exam::with(['subject', 'schoolClass'])
            ->withCount('attempts');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if (! $user->can('sys:manage_settings')) {
            $query->where('school_id', $user->school_id);
        }

        return $query;
    }

    /**
     * Get exam-level result data for the staff results page.
     *
     * @return array{exam: Exam, attempts: EloquentCollection<int, ExamAttempt>, totalQuestions: int}
     */
    public function getExamResults(Exam $exam): array
    {
        $exam->load(['subject', 'schoolClass']);

        $attempts = $exam->attempts()
            ->with(['user.schoolClass'])
            ->latest('submitted_at')
            ->get();

        return [
            'exam' => $exam,
            'attempts' => $attempts,
            'totalQuestions' => $exam->questions()->count(),
        ];
    }

    /**
     * Get student-specific result details for an exam.
     *
     * @return array{exam: Exam, student: User, attempt: ExamAttempt}
     */
    public function getStudentResult(Exam $exam, User $student): array
    {
        $exam->load(['subject', 'schoolClass']);

        $attempt = $exam->attempts()
            ->where('user_id', $student->id)
            ->with(['answers.question.options', 'answers.option'])
            ->firstOrFail();

        return [
            'exam' => $exam,
            'student' => $student,
            'attempt' => $attempt,
        ];
    }
}
