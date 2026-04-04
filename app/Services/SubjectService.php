<?php

namespace App\Services;

use App\DTOs\SubjectDTO;
use App\Models\Subject;
use Illuminate\Support\Str;

class SubjectService
{
    public function getIndexData(array $filters): array
    {
        return [
            'subjects' => $this->queryFilteredSubjects($filters)->latest()->paginate(10)->withQueryString(),
            'counts' => [
                'nursery' => Subject::query()->where('level', 'nursery')->count(),
                'primary' => Subject::query()->where('level', 'primary')->count(),
                'secondary' => Subject::query()->where('level', 'secondary')->count(),
            ],
        ];
    }

    public function createSubject(SubjectDTO $dto): Subject
    {
        return Subject::create([
            'name' => $dto->name,
            'slug' => Str::slug($dto->name.'-'.$dto->level),
            'description' => $dto->description,
            'level' => $dto->level,
        ]);
    }

    public function updateSubject(Subject $subject, SubjectDTO $dto): bool
    {
        return $subject->update([
            'name' => $dto->name,
            'slug' => Str::slug($dto->name.'-'.$dto->level),
            'description' => $dto->description,
            'level' => $dto->level,
        ]);
    }

    public function deleteSubject(Subject $subject): ?bool
    {
        if ($subject->topics()->exists()) {
            return false;
        }

        return $subject->delete();
    }

    private function queryFilteredSubjects(array $filters)
    {
        $query = Subject::query()->withCount('topics');

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query;
    }
}
