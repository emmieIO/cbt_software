<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Exam;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function login(): Response
    {
        return Inertia::render('Admin/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->login($request->credentials(), $request->boolean('remember'), 'admin');

        return to_route('admin.dashboard');
    }

    public function dashboard(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalStudents' => User::role('student')->count(),
                'totalStaff' => User::role('staff')->count(),
                'totalCandidates' => User::role('candidate')->count(),
                'totalQuestions' => \App\Models\Question::count(),
                'activeExams' => Exam::where('status', \App\Enums\ExamStatus::LIVE)->count(),
                'systemStatus' => 'Healthy',
            ],
            'recentExams' => Exam::with(['subject', 'schoolClass', 'prospectiveClass'])
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
                ]),
            'recentUsers' => User::latest()
                ->take(5)
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'User',
                    'joined_at' => $user->created_at->diffForHumans(),
                ]),
        ]);
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('home');
    }
}
