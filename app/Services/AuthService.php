<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $credentials, bool $remember, ?LoginRequest $loginRequest = null): User
    {
        if ($loginRequest) {
            $loginRequest->ensureIsNotRateLimited();
        }

        $guard = 'web';

        if (! Auth::guard($guard)->attempt($credentials, $remember)) {
            $this->failLogin(trans('auth.failed'), $guard, $loginRequest);
        }

        $user = Auth::guard($guard)->user();

        request()->session()->regenerate();

        if ($loginRequest) {
            $loginRequest->clearRateLimiter();
        }

        return $user;
    }

    /**
     * Terminate session and throw validation error.
     */
    protected function failLogin(string $message, string $guard = 'web', ?LoginRequest $loginRequest = null): void
    {
        if ($loginRequest) {
            $loginRequest->hitRateLimiter();
        }

        Auth::guard($guard)->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        throw ValidationException::withMessages([
            'login_id' => [$message],
        ]);
    }

    public function getRedirectUrl(User $user): string
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }

        return route('admin.dashboard');
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function logout(): void
    {
        // Unified logout from web guard
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
