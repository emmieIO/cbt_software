<?php

namespace App\Repositories\Eloquent;

use App\Models\AcademicSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Repositories\Contracts\AcademicRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentAcademicRepository implements AcademicRepositoryInterface
{
    public function getCurrentSession(): ?AcademicSession
    {
        return AcademicSession::query()->where('is_current', true)->first();
    }

    public function getActiveSchools(): Collection
    {
        return School::query()->where('is_active', true)->get();
    }

    public function getClasses(?string $schoolId = null): Collection
    {
        return SchoolClass::query()
            ->when($schoolId, function ($query, $resolvedSchoolId) {
                $query->where(function ($scoped) use ($resolvedSchoolId) {
                    // Classes can be global (school_id = null) or branch-specific.
                    $scoped->whereNull('school_id')
                        ->orWhere('school_id', $resolvedSchoolId);
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function getAllSubjects(bool $withTopics = false): Collection
    {
        return Subject::query()
            ->when($withTopics, fn ($q) => $q->with(['topics']))
            ->orderBy('name')
            ->get();
    }

    public function getTopics(string $subjectId, ?string $classId = null): Collection
    {
        return Topic::query()
            ->where('subject_id', $subjectId)
            ->when($classId, fn ($q) => $q->where('school_class_id', $classId))
            ->orderBy('name')
            ->get();
    }

    public function findClassById(string $id): ?SchoolClass
    {
        return SchoolClass::find($id);
    }

    public function findSubjectById(string $id): ?Subject
    {
        return Subject::find($id);
    }
}
