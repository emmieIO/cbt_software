<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StaffDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $currentSession = \App\Models\AcademicSession::current()->first();

        $assignments = $user->currentAssignments()->with(['subject', 'schoolClass', 'prospectiveClass'])->get();
        $assignedSubjectIds = $assignments->pluck('subject_id');
        $assignedClassIds = $assignments->pluck('school_class_id')->filter()->unique();
        $assignedProspectiveClassIds = $assignments->pluck('prospective_class_id')->filter()->unique();

        return Inertia::render('Staff/Dashboard', [
            'stats' => [
                'assignedClasses' => $assignments->count(),
                'pendingResults' => \App\Models\Exam::where('status', \App\Enums\ExamStatus::CLOSED)
                    ->whereIn('subject_id', $assignedSubjectIds)
                    ->whereHas('attempts')
                    ->count(),
                'questionBankCount' => \App\Models\Question::whereHas('topic', fn ($q) => $q->whereIn('subject_id', $assignedSubjectIds))->count(),
            ],
            'assignments' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'subject' => $a->subject->name,
                'class' => $a->schoolClass?->name ?? $a->prospectiveClass?->name,
            ]),
            'schedule' => \App\Models\Exam::whereIn('subject_id', $assignedSubjectIds)
                ->where(function ($query) use ($assignedClassIds, $assignedProspectiveClassIds) {
                    $query->whereIn('school_class_id', $assignedClassIds)
                        ->orWhereIn('prospective_class_id', $assignedProspectiveClassIds);
                })
                ->where('academic_session_id', $currentSession?->id)
                ->where('start_time', '>=', now())
                ->orderBy('start_time', 'asc')
                ->take(5)
                ->with(['subject', 'schoolClass', 'prospectiveClass'])
                ->get()
                ->map(fn ($exam) => [
                    'id' => $exam->id,
                    'title' => "{$exam->subject->name} - ".($exam->schoolClass?->name ?? $exam->prospectiveClass?->name),
                    'time' => $exam->start_time->isToday() ? 'Today, '.$exam->start_time->format('g:i A') : $exam->start_time->format('M d, g:i A'),
                    'location' => 'Main Hall',
                    'type' => 'Examination',
                    'color' => 'blue',
                ]),
        ]);
    }
}
