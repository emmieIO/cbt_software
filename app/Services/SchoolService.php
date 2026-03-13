<?php

namespace App\Services;

use App\DTOs\SchoolDTO;
use App\Models\School;
use Illuminate\Support\Str;

class SchoolService
{
    /**
     * Create a new school.
     */
    public function createSchool(SchoolDTO $dto): School
    {
        return School::create([
            ...$dto->toArray(),
            'slug' => Str::slug($dto->name),
        ]);
    }

    /**
     * Update an existing school.
     */
    public function updateSchool(School $school, SchoolDTO $dto): bool
    {
        $school->fill($dto->toArray());
        $school->slug = Str::slug($dto->name);
        
        return $school->save();
    }

    /**
     * Delete a school.
     */
    public function deleteSchool(School $school): bool
    {
        if ($school->users()->exists()) {
            return false;
        }

        return (bool) $school->delete();
    }
}
