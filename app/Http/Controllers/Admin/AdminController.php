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
                'activeExams' => Exam::where('status', \App\Enums\ExamStatus::LIVE)->count(),
                'systemStatus' => 'Healthy',
            ],
        ]);
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('home');
    }
}
