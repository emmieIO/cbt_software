<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * Get a paginated list of students.
     */
    public function getPaginatedStudents(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get a paginated list of staff members (any role in the 'staff' category).
     */
    public function getPaginatedStaff(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all students assigned to a specific branch.
     */
    public function getStudentsByBranch(string $branch): Collection;

    /**
     * Find a user by their ID.
     */
    public function findById(string $id): ?User;

    /**
     * Get users by a specific role.
     */
    public function getByRole(string $role): Collection;

    /**
     * Sync a user's school branch assignments.
     */
    public function syncSchools(string $userId, array $schoolIds, ?string $primarySchoolId = null): void;
}
