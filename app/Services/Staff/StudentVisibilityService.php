<?php

namespace App\Services\Staff;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;

class StudentVisibilityService
{
    /**
     * Build staff-scoped student listing data and filter context.
     *
     * @param  array{search?: string, school_class_id?: string}  $filters
     */
    public function getIndexData(User $staff, array $filters): array
    {
        $school = $staff->school_id ? School::find($staff->school_id) : null;

        $query = User::query()->role('candidate')
            ->where('status', 'active')
            ->where('school_id', $staff->school_id)
            ->with(['schoolClass']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['school_class_id'])) {
            $query->where('school_class_id', $filters['school_class_id']);
        }

        $classesQuery = SchoolClass::query();
        if ($school) {
            $classesQuery->where('level', $school->type);
        }

        return [
            'students' => $query->latest()->paginate(10)->withQueryString(),
            'classes' => $classesQuery->orderBy('name')->get(),
        ];
    }
}
