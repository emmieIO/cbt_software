<?php

namespace App\Services;

use App\DTOs\AdminDashboardDTO;
use App\Models\Exam;
use App\Models\Question;
use App\Models\School;
use App\Models\User;

class AdminDashboardService
{
    /**
     * Get data for the admin dashboard.
     */
    public function getDashboardData(): AdminDashboardDTO
    {
        $stats = [
            'totalStudents' => User::role('candidate')->count(),
            'totalStaff' => User::role('examiner')->count(),
            'totalCandidates' => User::role('candidate')->count(), // Both are candidate role now
            'totalQuestions' => Question::count(),
            'activeExams' => Exam::where('status', \App\Enums\ExamStatus::LIVE)->count(),
            'totalBranches' => School::count(),
            'systemStatus' => 'Healthy',
        ];

        $recentExams = Exam::with(['subject', 'schoolClass', 'prospectiveClass'])
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
                'target' => $exam->type === 'entrance' ? $exam->prospectiveClass?->name : $exam->schoolClass?->name,
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
