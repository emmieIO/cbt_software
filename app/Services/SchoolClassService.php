<?php

namespace App\Services;

use App\DTOs\SchoolClassDTO;
use App\Enums\ClassLevel;
use App\Models\SchoolClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SchoolClassService
{
    public function getIndexData(array $filters): array
    {
        $query = SchoolClass::query();

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return [
            'classes' => $query->orderBy('level')->orderBy('name')->paginate(10)->withQueryString(),
            'levels' => $this->levelOptions(),
        ];
    }

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

    private function levelOptions(): Collection
    {
        return collect(ClassLevel::cases())->map(fn ($level) => [
            'value' => $level->value,
            'label' => Str::title($level->value),
        ]);
    }
}
