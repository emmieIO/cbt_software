<?php

namespace App\Services;

use App\DTOs\AdminDashboardDTO;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Repositories\Contracts\AcademicRepositoryInterface;
use App\Repositories\Contracts\ExamRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

class AdminDashboardService
{
    public function __construct(
        protected ExamRepositoryInterface $examRepo,
        protected UserRepositoryInterface $userRepo,
        protected QuestionRepositoryInterface $questionRepo,
        protected AcademicRepositoryInterface $academicRepo
    ) {}

    /**
     * Get data for the admin dashboard.
     */
    public function getDashboardData(): AdminDashboardDTO
    {
        $stats = [
            'totalStudents' => $this->userRepo->getByRole('candidate')->count(),
            'totalStaff' => $this->userRepo->getByRole('examiner')->count(),
            'totalCandidates' => $this->userRepo->getByRole('candidate')->count(), // Unified concept
            'totalQuestions' => Question::count(), // Repository extension needed later for full decoupling
            'totalExams' => Exam::count(),
            'activeExams' => Exam::where('status', ExamStatus::LIVE)->count(),
            'totalBranches' => $this->academicRepo->getActiveSchools()->count(),
            'totalClasses' => $this->academicRepo->getClasses()->count(),
            'totalSubjects' => $this->academicRepo->getAllSubjects()->count(),
            'systemStatus' => 'Healthy',
        ];

        // Subject breakdown with question counts
        $subjectBreakdown = $this->academicRepo->getAllSubjects()
            ->map(fn ($subject) => [
                'name' => $subject->name,
                'count' => Question::whereHas('topic', function ($q) use ($subject) {
                    $q->where('subject_id', $subject->id);
                })->count(),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->toArray();

        $stats['subjectBreakdown'] = $subjectBreakdown;

        $recentExams = Exam::with(['subject', 'schoolClass'])
            ->withCount('attempts')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($exam) => [
                'id' => $exam->id,
                'title' => $exam->title,
                'status' => $exam->status,
                'type' => $exam->type,
                'subject' => $exam->subject?->name ?? 'Multi-Subject',
                'target' => $exam->schoolClass?->name ?? 'N/A',
                'attempts_count' => $exam->attempts_count,
                'date' => $exam->start_time?->format('M d, Y h:i A') ?? 'Not Scheduled',
            ])
            ->toArray();

        $recentUsers = User::latest()
            ->take(5)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'User',
                'joined_at' => $user->created_at->diffForHumans(),
            ])
            ->toArray();

        return new AdminDashboardDTO(
            stats: $stats,
            recentExams: $recentExams,
            recentUsers: $recentUsers,
        );
    }
}
