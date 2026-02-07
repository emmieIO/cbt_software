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
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(): Response
    {
        return Inertia::render('Staff/Login');
    }

    public function authenticate(LoginRequest $request): RedirectResponse
    {
        $redirectUrl = $this->authService->login($request->credentials(), $request->boolean('remember'));

        return redirect()->intended($redirectUrl);
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('home');
    }
}
