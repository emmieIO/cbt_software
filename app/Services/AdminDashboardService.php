<?php

namespace App\Services;

use App\DTOs\AdminDashboardDTO;
use App\Models\Exam;
use App\Models\Question;
use App\Models\School;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Subject;

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
            'totalCandidates' => User::role('candidate')->count(), 
            'totalQuestions' => Question::count(),
            'totalExams' => Exam::count(),
            'activeExams' => Exam::where('status', \App\Enums\ExamStatus::LIVE)->count(),
            'totalBranches' => School::count(),
            'totalClasses' => SchoolClass::count(),
            'totalSubjects' => Subject::count(),
            'systemStatus' => 'Healthy',
        ];

        // Subject breakdown with question counts
        $subjectBreakdown = Subject::all()
            ->map(fn ($subject) => [
                'name' => $subject->name,
                'count' => Question::whereHas('topic', function($q) use ($subject) {
                    $q->where('subject_id', $subject->id);
                })->count()
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->toArray();

        $stats['subjectBreakdown'] = $subjectBreakdown;

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
