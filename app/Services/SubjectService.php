<?php

namespace App\Services;

use App\DTOs\SubjectDTO;
use App\Models\Subject;
use Illuminate\Support\Str;

class SubjectService
{
    public function createSubject(SubjectDTO $dto): Subject
    {
        return Subject::create([
            'name' => $dto->name,
            'slug' => Str::slug($dto->name),
            'description' => $dto->description,
        ]);
    }

    public function updateSubject(Subject $subject, SubjectDTO $dto): bool
    {
        return $subject->update([
            'name' => $dto->name,
            'slug' => Str::slug($dto->name),
            'description' => $dto->description,
        ]);
    }

    public function deleteSubject(Subject $subject): ?bool
    {
        if ($subject->topics()->exists()) {
            return false;
        }

        return $subject->delete();
    }
}
