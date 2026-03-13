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
        // Unify all logins under the 'web' guard
        $guard = 'web';

        if (! Auth::guard($guard)->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'login_id' => [trans('auth.failed')],
            ]);
        }

        $user = Auth::guard($guard)->user();

        // Portal-specific security: Ensure the user belongs to this portal via Permissions
        if ($requiredRole) {
            $hasAccess = match ($requiredRole) {
                'student', 'candidate' => $user->can('exam:take'),
                'staff', 'examiner' => $user->can('bank:view'),
                'admin', 'super_admin' => $user->can('sys:manage_settings'),
                default => $user->can($requiredRole), // Fallback to permission check if string is passed
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
     * Get the appropriate dashboard URL based on the user's permissions.
     */
    public function getRedirectUrl(User $user): string
    {
        if ($user->can('sys:manage_settings')) {
            return route('admin.dashboard');
        }

        if ($user->can('bank:view')) {
            return route('staff.dashboard');
        }

        if ($user->can('exam:take')) {
            return route('student.dashboard');
        }

        return route('home');
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
