<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StaffAuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function login(): Response
    {
        return Inertia::render('Staff/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->login($request->credentials(), $request->boolean('remember'), 'access:staff-portal', $request);

        return redirect()->intended(route('staff.dashboard'));
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('home');
    }
}
