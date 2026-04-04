<?php

namespace App\Services\Admin;

use App\DTOs\UserDTO;
use App\Models\School;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Spatie\Permission\Models\Role;

class StaffManagementService
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected UserService $userService
    ) {}

    public function getIndexData(array $filters): array
    {
        return [
            'staff' => $this->userRepo->getPaginatedStaff(10, $filters),
            'branches' => School::query()->where('is_active', true)->get(),
        ];
    }

    public function getFormContext(): array
    {
        return [
            'branches' => School::query()->where('is_active', true)->get(),
            'roles' => Role::query()->where('category', 'staff')->get(),
        ];
    }

    public function createStaff(UserDTO $dto, string $role, array $schoolIds, ?string $primarySchoolId = null): User
    {
        $dto->status = 'active';
        $user = $this->userService->createUser($dto, $role);

        $this->userRepo->syncSchools(
            $user->id,
            $schoolIds,
            $primarySchoolId ?? $schoolIds[0]
        );

        return $user;
    }

    public function updateStaff(User $staff, UserDTO $dto, string $role, array $schoolIds, ?string $primarySchoolId = null): void
    {
        $this->userService->updateUser($staff, $dto);
        $staff->syncRoles([$role]);

        $this->userRepo->syncSchools(
            $staff->id,
            $schoolIds,
            $primarySchoolId ?? $schoolIds[0]
        );
    }

    public function deleteStaff(User $staff): bool
    {
        return $this->userService->deleteUser($staff);
    }
}
