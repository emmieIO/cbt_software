<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function create(): Response
    {
        return Inertia::render('Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $this->authService->login($request->credentials(), $request->boolean('remember'), $request);

        return to_route('dashboard');
    }

    public function destroy(): RedirectResponse
    {
        $this->authService->logout();

        return to_route('login');
    }
}
