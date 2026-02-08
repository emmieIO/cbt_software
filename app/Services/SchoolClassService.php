<?php

namespace App\Services;

use App\DTOs\SchoolClassDTO;
use App\Models\SchoolClass;
use Illuminate\Support\Str;

class SchoolClassService
{
    public function createClass(SchoolClassDTO $dto): SchoolClass
    {
        return SchoolClass::create([
            'name' => $dto->name,
            'slug' => Str::slug($dto->name),
            'level' => $dto->level,
        ]);
    }

    public function updateClass(SchoolClass $schoolClass, SchoolClassDTO $dto): bool
    {
        return $schoolClass->update([
            'name' => $dto->name,
            'slug' => Str::slug($dto->name),
            'level' => $dto->level,
        ]);
    }

    public function deleteClass(SchoolClass $schoolClass): ?bool
    {
        if ($schoolClass->questions()->exists()) {
            return false;
        }

        return $schoolClass->delete();
    }
}
