<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class AccessRecoveryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{users: LengthAwarePaginator, roles: \Illuminate\Database\Eloquent\Collection<int, Role>}
     */
    public function getIndexData(array $filters): array
    {
        $query = User::query()
            ->doesntHave('roles')
            ->with(['school', 'schoolClass'])
            ->when($filters['search'] ?? null, function ($builder, $search) {
                $builder->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['school_id'] ?? null, fn ($builder, $schoolId) => $builder->where('school_id', $schoolId));

        return [
            'users' => $query->latest()->paginate(12)->through(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'status' => $user->status,
                    'school' => $user->school ? [
                        'id' => $user->school->id,
                        'name' => $user->school->name,
                    ] : null,
                    'school_class' => $user->schoolClass ? [
                        'id' => $user->schoolClass->id,
                        'name' => $user->schoolClass->name,
                    ] : null,
                    'suggested_category' => $this->suggestCategory($user),
                ];
            })->withQueryString(),
            'roles' => Role::query()
                ->whereIn('category', ['admin', 'staff', 'student'])
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'category']),
        ];
    }

    public function reassignRole(User $user, string $roleName): void
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user->syncRoles([$role->name]);
    }

    private function suggestCategory(User $user): string
    {
        if ($user->school_class_id || $user->prospective_class_id) {
            return 'student';
        }

        return 'staff';
    }
}

