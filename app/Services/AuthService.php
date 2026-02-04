<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Attempt to authenticate a user.
     *
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false): string
    {
        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'login_id' => [trans('auth.failed')],
            ]);
        }

        request()->session()->regenerate();

        return $this->getRedirectUrl(Auth::user());
    }

    /**
     * Get the redirect URL based on the user's role.
     */
    public function getRedirectUrl(User $user): string
    {
        if ($user->hasRole('admin')) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('staff')) {
            return route('staff.dashboard');
        }

        if ($user->hasRole('student')) {
            return route('student.dashboard');
        }

        return route('home');
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
