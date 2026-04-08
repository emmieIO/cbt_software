<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user and assign a role.
     */
    public function createUser(UserDTO $dto, string $role): User
    {
        $userData = $dto->toArray();

        if (! isset($userData['password'])) {
            $userData['password'] = Hash::make('chrisland123');
        }

        if ($role === 'candidate' && ! isset($userData['status'])) {
            $userData['status'] = 'prospective';
        }

        if (empty($userData['username'])) {
            $userData['username'] = $this->generateUsername($role);
        }

        $user = User::create($userData);
        $user->assignRole($role);

        // Keep class enrollment in sync for active candidate accounts.
        if ($role === 'candidate' && $user->school_class_id && ($user->status === 'active' || ! isset($userData['status']))) {
            $currentSession = AcademicSession::current()->first();
            if ($currentSession) {
                ClassEnrollment::updateOrCreate(
                    ['user_id' => $user->id, 'academic_session_id' => $currentSession->id],
                    ['school_class_id' => $user->school_class_id]
                );
            }
        }

        return $user;
    }

    /**
     * Generate the next sequential username for a role and current year.
     */
    private function generateUsername(string $role): string
    {
        $prefix = match ($role) {
            'examiner' => 'STAFF',
            'candidate' => 'CHS',
            default => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $role) ?: 'USER'),
        };

        $year = now()->format('Y');
        $pattern = $prefix.'/'.$year.'/%';

        $latest = User::query()
            ->where('username', 'like', $pattern)
            ->pluck('username')
            ->map(function (string $username): int {
                $lastSegment = (string) str($username)->afterLast('/');

                return (int) preg_replace('/\D/', '', $lastSegment);
            })
            ->max() ?? 0;

        $next = $latest + 1;

        do {
            $candidate = sprintf('%s/%s/%03d', $prefix, $year, $next);
            $exists = User::query()->where('username', $candidate)->exists();
            $next++;
        } while ($exists);

        return $candidate;
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, UserDTO $dto): User
    {
        $user->update($dto->toArray());

        return $user;
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): bool
    {
        if ($user->can('sys:manage_settings')) {
            return false;
        }

        return $user->delete();
    }
}
