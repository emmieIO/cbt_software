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
    public function login(array $credentials, bool $remember, string $requiredPermission): User
    {
        // Unify all logins under the 'web' guard
        $guard = 'web';

        if (! Auth::guard($guard)->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'login_id' => [trans('auth.failed')],
            ]);
        }

        $user = Auth::guard($guard)->user();

        // Portal-specific security: Allow Super Admin (sys:manage_settings) to bypass,
        // otherwise check for the specific portal access permission.
        $isAuthorized = $user->can('sys:manage_settings') || $user->can($requiredPermission);

        if (! $isAuthorized) {
            $this->failLogin(trans('auth.failed'), $guard);
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
