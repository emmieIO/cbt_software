<?php

namespace App\Services;

use App\DTOs\StaffDashboardDTO;
use App\Enums\ExamStatus;
use App\Models\User;
use App\Repositories\Contracts\AcademicRepositoryInterface;
use App\Repositories\Contracts\ExamRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use Illuminate\Support\Collection;

class StaffDashboardService
{
    public function __construct(
        protected ExamRepositoryInterface $examRepo,
        protected QuestionRepositoryInterface $questionRepo,
        protected AcademicRepositoryInterface $academicRepo
    ) {}

    /**
     * Get data for the staff dashboard.
     * Scoped by the staff member's school branch.
     */
    public function getDashboardData(User $user): StaffDashboardDTO
    {
        $currentSession = $this->academicRepo->getCurrentSession();
        $schoolId = $user->school_id;

        // In the new model, staff see everything in their branch
        $availableClasses = $this->academicRepo->getClasses($schoolId);
        $availableSubjects = $this->academicRepo->getAllSubjects(); // Usually global, but can be filtered if needed

        return new StaffDashboardDTO(
            stats: $this->calculateStats($schoolId),
            assignments: $this->formatAvailableScope($availableClasses),
            schedule: $this->getUpcomingSchedule($schoolId, $currentSession?->id)
        );
    }

    /**
     * Calculate summary statistics for the branch.
     */
    private function calculateStats(?string $schoolId): array
    {
        return [
            'assignedClasses' => \App\Models\SchoolClass::where('school_id', $schoolId)->count(),
            'pendingResults' => \App\Models\Exam::where('school_id', $schoolId)
                ->where('status', ExamStatus::CLOSED)
                ->whereHas('attempts')
                ->count(),
            'questionBankCount' => \App\Models\Question::whereHas('schoolClass', fn ($q) => $q->where('school_id', $schoolId))->count(),
        ];
    }

    /**
     * Format the available classes/scope for the frontend.
     */
    private function formatAvailableScope(Collection $classes): array
    {
        return $classes->map(fn ($c) => [
            'id' => $c->id,
            'subject' => 'All Subjects (Branch Examiner)',
            'class' => $c->name,
        ])->toArray();
    }

    /**
     * Get upcoming exam schedule for the branch.
     */
    private function getUpcomingSchedule(?string $schoolId, ?string $sessionId): array
    {
        return \App\Models\Exam::where('school_id', $schoolId)
            ->where('academic_session_id', $sessionId)
            ->where('start_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->with(['subject', 'schoolClass'])
            ->get()
            ->map(fn ($exam) => [
                'id' => $exam->id,
                'title' => ($exam->subject?->name ?? 'Assessment').' - '.($exam->schoolClass?->name ?? 'N/A'),
                'time' => $exam->start_time->isToday() ? 'Today, '.$exam->start_time->format('g:i A') : $exam->start_time->format('M d, g:i A'),
                'location' => 'Main Hall',
                'type' => 'Examination',
                'color' => 'blue',
            ])
            ->toArray();
    }
}
