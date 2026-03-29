<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function getPaginatedStudents(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->role('candidate')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['school_id'] ?? null, fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->when($filters['school_class_id'] ?? null, fn ($q, $classId) => $q->where('school_class_id', $classId))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getPaginatedStaff(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->where('category', 'staff');
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['school_id'] ?? null, fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->with('roles')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getStudentsByBranch(string $branch): Collection
    {
        return User::role('candidate')
            ->where('branch', $branch)
            ->get();
    }

    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function getByRole(string $role): Collection
    {
        return User::role($role)->get();
    }

    public function syncSchools(string $userId, array $schoolIds, ?string $primarySchoolId = null): void
    {
        $user = User::findOrFail($userId);

        $syncData = [];
        foreach ($schoolIds as $id) {
            $syncData[$id] = [
                'is_primary' => $id === $primarySchoolId,
            ];
        }

        $user->schools()->sync($syncData);
    }
}
