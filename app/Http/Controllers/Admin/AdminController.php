<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AdminDashboardService;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected AdminDashboardService $dashboardService
    ) {}

    public function login(): Response
    {
        return Inertia::render('Admin/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->login($request->credentials(), $request->boolean('remember'), 'super_admin');

        return to_route('admin.dashboard');
    }

    public function dashboard(): Response
    {
        $data = $this->dashboardService->getDashboardData();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $data->stats,
            'recentExams' => $data->recentExams,
            'recentUsers' => $data->recentUsers,
        ]);
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('home');
    }
}
