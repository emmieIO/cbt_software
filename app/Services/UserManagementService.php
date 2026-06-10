<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'permissions' => $this->permissionsForRole($data),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(User $user, array $data): void
    {
        $payload = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $data['role'],
            'permissions' => $this->permissionsForRole($data),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function permissionsForRole(array $data): array
    {
        if (($data['role'] ?? null) === User::ROLE_ADMIN) {
            return [];
        }

        return array_values(array_intersect($data['permissions'] ?? [], User::QUESTION_PERMISSIONS));
    }
}
