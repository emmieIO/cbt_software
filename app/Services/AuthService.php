<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Attempt to authenticate a user and verify their role for the target portal.
     *
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false, ?string $requiredRole = null): User
    {
        $guard = match ($requiredRole) {
            'admin' => 'admin',
            'staff' => 'staff',
            'student' => 'student',
            default => 'web',
        };

        if (! Auth::guard($guard)->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'login_id' => [trans('auth.failed')],
            ]);
        }

        $user = Auth::guard($guard)->user();

        // Portal-specific security: Ensure the user belongs to this portal
        if ($requiredRole) {
            $hasAccess = match ($requiredRole) {
                'student' => $user->hasRole('student') || $user->hasRole('candidate'),
                'staff' => $user->hasRole('staff') || $user->hasRole('subject_lead'),
                'admin' => $user->hasRole('admin'),
                default => $user->hasRole($requiredRole),
            };

            if (! $hasAccess) {
                $this->failLogin('Access denied. You do not have the required permissions for this portal.', $guard);
            }
        }

        request()->session()->regenerate();

        return $user;
    }

    /**
     * Terminate session and throw validation error.
     */
    protected function failLogin(string $message, string $guard = 'web'): void
    {
        Auth::guard($guard)->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        throw ValidationException::withMessages([
            'login_id' => [$message],
        ]);
    }

    /**
     * Get the appropriate dashboard URL based on the user's roles.
     */
    public function getRedirectUrl(User $user): string
    {
        if ($user->hasRole('admin')) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('staff') || $user->hasRole('subject_lead')) {
            return route('staff.dashboard');
        }

        if ($user->hasRole('student') || $user->hasRole('candidate')) {
            return route('student.dashboard');
        }

        return route('home');
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function logout(): void
    {
        // Logout from all possible guards to be safe
        foreach (['admin', 'staff', 'student', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
