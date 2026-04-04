<?php

namespace App\Services\Admin;

use App\DTOs\UserDTO;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\UserService;
use Spatie\Permission\Models\Role;

class StudentManagementService
{
    public function __construct(protected UserService $userService) {}

    public function getIndexData(array $filters): array
    {
        $query = User::role('candidate')
            ->where('status', 'active')
            ->with(['schoolClass', 'roles', 'school']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }

        if (! empty($filters['school_class_id'])) {
            $query->where('school_class_id', $filters['school_class_id']);
        }

        return [
            'students' => $query->latest()->paginate(10)->withQueryString(),
            'classes' => SchoolClass::all(),
            'branches' => School::query()->where('is_active', true)->get(),
        ];
    }

    public function getFormContext(): array
    {
        return [
            'classes' => SchoolClass::all(),
            'branches' => School::query()->where('is_active', true)->get(),
            'roles' => Role::query()->where('category', 'student')->get(),
        ];
    }

    public function createCandidate(UserDTO $dto, string $role): User
    {
        $dto->status = 'active';

        return $this->userService->createUser($dto, $role);
    }

    public function updateCandidate(User $student, UserDTO $dto, string $role): void
    {
        $this->userService->updateUser($student, $dto);
        $student->syncRoles([$role]);
    }

    public function deleteCandidate(User $student): bool
    {
        return $this->userService->deleteUser($student);
    }
}
