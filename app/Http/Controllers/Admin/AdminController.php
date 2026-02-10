<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
        $redirectUrl = $this->authService->login($request->credentials(), $request->boolean('remember'), 'admin');

        return redirect()->intended($redirectUrl);
    }

    public function dashboard(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalStudents' => \App\Models\User::role('student')->count(),
                'totalStaff' => \App\Models\User::role('staff')->count(),
                'activeExams' => \App\Models\Exam::where('status', \App\Enums\ExamStatus::LIVE)->count(),
                'systemStatus' => 'Healthy',
            ],
            'recentActivity' => [
                [
                    'id' => 1,
                    'user' => 'System',
                    'action' => 'Activity logging feature pending implementation',
                    'time' => 'N/A',
                ],
            ],
        ]);
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('home');
    }
}
