<?php

namespace App\Repositories\Contracts;

use App\Models\AcademicSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;

interface AcademicRepositoryInterface
{
    /**
     * Get the current active academic session.
     */
    public function getCurrentSession(): ?AcademicSession;

    /**
     * Get all active schools/branches.
     */
    public function getActiveSchools(): Collection;

    /**
     * Get all classes, optionally filtered by school.
     */
    public function getClasses(?string $schoolId = null): Collection;

    /**
     * Get all subjects.
     */
    public function getAllSubjects(): Collection;

    /**
     * Get topics for a specific subject and class.
     */
    public function getTopics(string $subjectId, ?string $classId = null): Collection;

    /**
     * Find a class by ID.
     */
    public function findClassById(string $id): ?SchoolClass;

    /**
     * Find a subject by ID.
     */
    public function findSubjectById(string $id): ?Subject;
}
