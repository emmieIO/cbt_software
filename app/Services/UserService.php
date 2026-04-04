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
