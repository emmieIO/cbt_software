<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    /**
     * Promote a batch of students to a new class.
     */
    public function promote(SchoolClass $fromClass, ?SchoolClass $toClass, array $studentIds): int
    {
        return DB::transaction(function () use ($fromClass, $toClass, $studentIds) {
            $currentSession = AcademicSession::current()->firstOrFail();

            $query = User::whereIn('id', $studentIds)
                ->where('school_class_id', $fromClass->id);

            if ($toClass) {
                // Promote to next level
                $count = $query->update([
                    'school_class_id' => $toClass->id,
                    'status' => 'active',
                ]);

                // Record history for each student
                foreach ($studentIds as $userId) {
                    ClassEnrollment::updateOrCreate(
                        ['user_id' => $userId, 'academic_session_id' => $currentSession->id],
                        ['school_class_id' => $toClass->id]
                    );
                }

                return $count;
            } else {
                // Graduation path
                return $query->update([
                    'school_class_id' => null,
                    'status' => 'graduated',
                    'is_active' => false,
                ]);
            }
        });
    }

    /**
     * Get a summary of all classes and their population statuses.
     */
    public function getClassStatusSummary(): Collection
    {
        return SchoolClass::withCount(['users' => function ($query) {
            $query->where('status', 'active');
        }])->get()->map(function ($class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'level' => $class->level,
                'student_count' => $class->users_count,
                'is_empty' => $class->users_count === 0,
            ];
        });
    }
}
