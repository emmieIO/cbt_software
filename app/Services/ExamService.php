<?php

namespace App\Services;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\Exam\AttemptLifecycleService;
use App\Services\Exam\AttemptSubmissionService;
use App\Services\Exam\QuestionSelectionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function __construct(
        protected QuestionSelectionService $questionSelectionService,
        protected AttemptLifecycleService $attemptLifecycleService,
        protected AttemptSubmissionService $attemptSubmissionService
    ) {}

    /**
     * Create a new exam.
     */
    public function createExam(array $data, string $creatorId): Exam
    {
        return DB::transaction(function () use ($data, $creatorId) {
            $compositions = $data['compositions'] ?? [];
            unset($data['compositions']);

            $exam = Exam::create([
                ...$data,
                'created_by' => $creatorId,
                'status' => ExamStatus::DRAFT,
            ]);

            foreach ($compositions as $compDTO) {
                $exam->compositions()->create($compDTO->toArray());
            }

            return $exam;
        });
    }

    /**
     * Update questions for a specific exam.
     */
    public function updateExamQuestions(Exam $exam, array $questionIds): void
    {
        $exam->questions()->sync($questionIds);
    }

    /**
     * Automatically select questions for an exam using the biennial rotation policy.
     */
    public function autoSelectQuestions(Exam $exam, ?int $totalCount = null): int
    {
        return $this->questionSelectionService->autoSelectQuestions($exam, $totalCount);
    }

    /**
     * Get available questions for an exam context.
     */
    public function getAvailableQuestions(Exam $exam): Collection
    {
        return $this->questionSelectionService->getAvailableQuestions($exam);
    }

    /**
     * Start a new exam attempt for a student.
     */
    public function startExam(User $user, Exam $exam): ExamAttempt
    {
        return $this->attemptLifecycleService->startExam($user, $exam);
    }

    /**
     * Get the questions for an attempt in their locked-in order.
     */
    public function getAttemptQuestions(ExamAttempt $attempt): Collection
    {
        return $this->attemptLifecycleService->getAttemptQuestions($attempt);
    }

    /**
     * Submit an exam attempt and calculate the score.
     */
    public function submitAttempt(ExamAttempt $attempt, array $answers, array $additionalMetadata = [], array $violations = []): void
    {
        $this->attemptSubmissionService->submitAttempt($attempt, $answers, $additionalMetadata, $violations);
    }

    public function attemptHasTimedOut(ExamAttempt $attempt): bool
    {
        return $this->attemptSubmissionService->hasTimedOut($attempt);
    }
}
