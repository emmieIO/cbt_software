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
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();
    }

    public function getAllSubjects(): Collection
    {
        return Subject::query()->orderBy('name')->get();
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
