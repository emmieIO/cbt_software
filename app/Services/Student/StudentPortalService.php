<?php

namespace App\Services\Student;

use App\Enums\AttemptStatus;
use App\Enums\ExamStatus;
use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StudentPortalService
{
    public function getDashboardData(User $user): array
    {
        $upcomingExams = $this->queryUpcomingExamsForDashboard($user)
            ->orderBy('start_time', 'asc')
            ->take(4)
            ->get();

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
        return $this->queryScopedExams($user)
            ->with(['subject'])
            ->with(['attempts' => fn ($q) => $q->where('user_id', $user->id)])
            ->withCount('questions')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Exam $exam) => $this->mapExamForListing($exam));
    }

    public function userCanAccessExam(User $user, Exam $exam): bool
    {
        return $this->getExamAccessDenialReason($user, $exam) === null;
    }

    public function getExamAccessDenialReason(User $user, Exam $exam): ?string
    {
        $status = $exam->status instanceof ExamStatus ? $exam->status : ExamStatus::tryFrom((string) $exam->status);

        if ($status !== ExamStatus::LIVE) {
            return 'This examination is not live yet.';
        }

        $startTime = $this->parseDateTime($exam->start_time);
        if ($startTime === null || $startTime->isFuture()) {
            return 'This examination has not opened yet.';
        }

        if ($this->examWindowHasClosed($exam)) {
            return 'This examination window has closed.';
        }

        if (! $this->examMatchesUserSchool($exam, $user)) {
            return 'This examination is not available for your school branch.';
        }

        if ($exam->users()->whereKey($user->id)->exists()) {
            return null;
        }

        $eligibleClassIds = $this->resolveEligibleClassIds($user, $exam->academic_session_id);

        if ($exam->school_class_id !== null && in_array($exam->school_class_id, $eligibleClassIds, true)) {
            return null;
        }

        return 'This examination is not available for your class.';
    }

    public function examWindowHasClosed(Exam $exam): bool
    {
        $endTime = $this->parseDateTime($exam->end_time);

        return $endTime !== null && $endTime->isPast();
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

    public function isValidAttemptAnswerSelection(ExamAttempt $attempt, string $questionId, string $optionId): bool
    {
        $metadata = $attempt->metadata ?? [];
        $questionOrder = $metadata['question_order'] ?? [];
        $optionOrders = $metadata['option_orders'] ?? [];

        if (! is_array($questionOrder) || ! in_array($questionId, $questionOrder, true)) {
            return false;
        }

        $questionOptions = $optionOrders[$questionId] ?? null;

        return is_array($questionOptions) && in_array($optionId, $questionOptions, true);
    }

    private function queryUpcomingExamsForDashboard(User $user): Builder
    {
        return $this->queryScopedExams($user)
            ->with(['subject'])
            ->withCount('questions')
            ->where(function ($query) {
                $query->whereNull('end_time')
                    ->orWhere('end_time', '>=', now());
            })
            ->whereDoesntHave('attempts', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', AttemptStatus::SUBMITTED));
    }

    public function queryEligibleExams(User $user, ?string $sessionId = null): Builder
    {
        return $this->queryScopedExams($user, $sessionId)
            ->where(function ($query) {
                $query->whereNull('start_time')
                    ->orWhere('start_time', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_time')
                    ->orWhere('end_time', '>=', now());
            });
    }

    public function queryScopedExams(User $user, ?string $sessionId = null): Builder
    {
        $eligibleClassIds = $this->resolveEligibleClassIds($user, $sessionId);

        return Exam::query()
            ->where('status', ExamStatus::LIVE)
            ->whereNotNull('start_time')
            ->when($sessionId, fn ($query, $sessionId) => $query->where('academic_session_id', $sessionId))
            ->where(function ($query) use ($user) {
                if ($user->school_id) {
                    $query->where(function ($schoolScoped) use ($user) {
                        $schoolScoped->whereNull('school_id')
                            ->orWhere('school_id', $user->school_id);
                    });
                }
            })
            ->where(function ($query) use ($user, $eligibleClassIds) {
                $query->whereHas('users', fn ($assigned) => $assigned->where('user_id', $user->id));

                if ($eligibleClassIds !== []) {
                    $query->orWhereIn('school_class_id', $eligibleClassIds);
                }
            });
    }

    /**
     * @return list<string>
     */
    protected function resolveEligibleClassIds(User $user, ?string $sessionId = null): array
    {
        $enrolledClassIds = ClassEnrollment::query()
            ->where('user_id', $user->id)
            ->when($sessionId, fn ($query, $sessionId) => $query->where('academic_session_id', $sessionId))
            ->pluck('school_class_id')
            ->filter()
            ->all();

        return array_values(array_unique(array_filter([
            $user->school_class_id,
            ...$enrolledClassIds,
        ])));
    }

    protected function examMatchesUserSchool(Exam $exam, User $user): bool
    {
        return ! $exam->school_id || ! $user->school_id || $exam->school_id === $user->school_id;
    }

    protected function mapExamForListing(Exam $exam): array
    {
        $attempts = $exam->attempts->map(function (ExamAttempt $attempt) {
            $status = $attempt->status;

            return [
                'id' => $attempt->id,
                'status' => $status instanceof AttemptStatus ? $status->value : (string) $status,
            ];
        })->values();

        return [
            ...$exam->toArray(),
            'subject' => $exam->subject?->toArray(),
            'attempts' => $attempts,
            'start_time' => $this->formatDateTime($exam->start_time),
            'end_time' => $this->formatDateTime($exam->end_time),
            'availability_status' => $this->determineAvailabilityStatus($exam),
        ];
    }

    protected function determineAvailabilityStatus(Exam $exam): string
    {
        /** @var ExamAttempt|null $attempt */
        $attempt = $exam->attempts->first();
        $attemptStatus = $attempt?->status instanceof AttemptStatus
            ? $attempt->status->value
            : (is_string($attempt?->status) ? $attempt->status : null);

        if ($attemptStatus === AttemptStatus::SUBMITTED->value) {
            return 'completed';
        }

        if ($attemptStatus === AttemptStatus::ONGOING->value) {
            return $this->examWindowHasClosed($exam) ? 'expired' : 'ongoing';
        }

        if ($this->examWindowHasClosed($exam)) {
            return 'missed';
        }

        $startTime = $this->parseDateTime($exam->start_time);
        if ($startTime !== null && $startTime->isFuture()) {
            return 'scheduled';
        }

        return 'available';
    }

    protected function formatDateTime(mixed $value): ?string
    {
        return $this->parseDateTime($value)?->format('Y-m-d H:i:s');
    }

    protected function parseDateTime(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return Carbon::parse($value);
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
