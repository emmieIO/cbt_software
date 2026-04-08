<?php

namespace App\Services\Exam;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\User;
use App\Services\QuestionService;

class ExamPayloadService
{
    public function __construct(protected QuestionService $questionService) {}

    private function canAuthorAcrossLevels(User $user): bool
    {
        return $user->can('access:cross-level-authoring')
            || $user->can('bank:create_cross_level')
            || $user->can('exam:create_cross_level');
    }

    /**
     * Build common create/edit form payload for exam screens.
     */
    public function getFormPayload(User $user): array
    {
        $context = $this->questionService->getAuthorizedContext(
            $user,
            false,
            $this->canAuthorAcrossLevels($user)
        );

        return [
            'sessions' => AcademicSession::query()->current()->get(),
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
        ];
    }

    /**
     * Build edit payload with loaded exam relationships.
     */
    public function getEditPayload(User $user, Exam $exam): array
    {
        return [
            'exam' => $exam->load(['subject', 'schoolClass', 'compositions.subject', 'compositions.topic']),
            ...$this->getFormPayload($user),
        ];
    }
}
