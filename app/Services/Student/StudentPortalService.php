<?php

namespace App\Services\Student;

use App\Enums\AttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentPortalService
{
    public function getDashboardData(User $user): array
    {
        $currentSession = AcademicSession::current()->first();

        $upcomingExams = $currentSession
            ? $this->queryUpcomingExamsForDashboard($user, $currentSession->id)->orderBy('start_time', 'asc')->take(4)->get()
            : collect();

        $recentResults = $this->getRecentResults($user, 5);

        return [
            'upcomingExams' => $upcomingExams,
            'recentResults' => $recentResults,
            'stats' => [
                'examsTaken' => ExamAttempt::query()
                    ->where('user_id', $user->id)
                    ->where('status', AttemptStatus::SUBMITTED)
                    ->count(),
                'averageScore' => $this->calculateAverageScore($recentResults),
                'pendingExams' => $upcomingExams->count(),
            ],
        ];
    }

    public function getAvailableExams(User $user): Collection
    {
        $currentSession = AcademicSession::current()->first();
        if (! $currentSession) {
            return collect();
        }

        $query = Exam::query()
            ->where('academic_session_id', $currentSession->id)
            ->where('status', ExamStatus::LIVE)
            ->with(['subject'])
            ->with(['attempts' => fn ($q) => $q->where('user_id', $user->id)])
            ->withCount('questions');

        if ($user->status === 'candidate') {
            $query->where('type', ExamType::ENTRANCE)
                ->where('prospective_class_id', $user->prospective_class_id);
        } else {
            $query->where('school_class_id', $user->school_class_id);
        }

        return $query->get();
    }

    public function getResultsHistory(User $user): Collection
    {
        return ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::SUBMITTED)
            ->with(['exam' => fn ($q) => $q->with(['subject'])->withCount('questions')])
            ->latest('submitted_at')
            ->get();
    }

    public function saveAnswer(ExamAttempt $attempt, string $questionId, string $optionId): void
    {
        $metadata = $attempt->metadata;
        $savedAnswers = $metadata['saved_answers'] ?? [];
        $savedAnswers[$questionId] = $optionId;

        $metadata['saved_answers'] = $savedAnswers;

        $attempt->update(['metadata' => $metadata]);
    }

    private function queryUpcomingExamsForDashboard(User $user, string $sessionId): Builder
    {
        $query = Exam::query()
            ->where('academic_session_id', $sessionId)
            ->where('status', ExamStatus::LIVE)
            ->with(['subject'])
            ->withCount('questions')
            ->whereDoesntHave('attempts', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', AttemptStatus::SUBMITTED));

        $query->where(function ($q) use ($user) {
            $q->whereHas('users', fn ($sq) => $sq->where('user_id', $user->id))
                ->orWhere('school_class_id', $user->school_class_id);
        });

        return $query;
    }

    private function getRecentResults(User $user, int $limit): Collection
    {
        return ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::SUBMITTED)
            ->with(['exam' => fn ($q) => $q->with(['subject'])->withCount('questions')])
            ->latest('submitted_at')
            ->take($limit)
            ->get();
    }

    private function calculateAverageScore(Collection $results): int
    {
        if ($results->isEmpty()) {
            return 0;
        }

        $totalPercentage = 0;

        foreach ($results as $result) {
            $maxScore = $result->exam->questions_count ?: 1;
            $totalPercentage += ($result->score / $maxScore) * 100;
        }

        return (int) round($totalPercentage / $results->count());
    }
}
