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

        return Inertia::render('Staff/Dashboard', [
            'stats' => [
                'assignedClasses' => \App\Models\TeacherAssignment::where('user_id', $user->id)->count(),
                'pendingResults' => \App\Models\Exam::where('status', \App\Enums\ExamStatus::CLOSED)
                    ->whereHas('attempts')
                    ->count(),
                'questionBankCount' => \App\Models\Question::count(),
            ],
            'schedule' => [
                // Mock schedule for now as we don't have a dedicated schedule/timetable table
                [
                    'id' => 1,
                    'title' => 'Physics - SS2 Blue',
                    'time' => 'Today, 2:00 PM',
                    'location' => 'Examination Hall A',
                    'type' => 'Invigilation',
                    'color' => 'blue',
                ],
                [
                    'id' => 2,
                    'title' => 'General Math - SS1 Green',
                    'time' => 'Today, 4:30 PM',
                    'location' => 'Classroom 4B',
                    'type' => 'Class Session',
                    'color' => 'purple',
                ],
            ],
        ]);
    }
}
