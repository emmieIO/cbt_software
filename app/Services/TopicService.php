<?php

namespace App\Services;

use App\DTOs\TopicDTO;
use App\Enums\ClassLevel;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TopicService
{
    public function getIndexData(User $user, array $filters): array
    {
        $query = Topic::query()->with(['subject', 'schoolClass'])->withCount('questions');

        [$subjects, $classes] = $this->resolveContext($user, $query);

        $this->applyFilters($query, $filters);

        return [
            'topics' => $query->orderBy('name')->paginate(10)->withQueryString(),
            'subjects' => $subjects,
            'classes' => $classes,
            'levels' => $this->levelOptions(),
        ];
    }

    public function createTopic(TopicDTO $dto): Topic
    {
        return Topic::create([
            'subject_id' => $dto->subject_id,
            'school_class_id' => $dto->school_class_id,
            'name' => $dto->name,
            'slug' => Str::slug($dto->name.'-'.Str::random(5)),
            'description' => $dto->description,
        ]);
    }

    public function updateTopic(Topic $topic, TopicDTO $dto): bool
    {
        return $topic->update([
            'subject_id' => $dto->subject_id,
            'school_class_id' => $dto->school_class_id,
            'name' => $dto->name,
            'slug' => Str::slug($dto->name.'-'.Str::random(5)),
            'description' => $dto->description,
        ]);
    }

    public function deleteTopic(Topic $topic): ?bool
    {
        if ($topic->questions()->exists()) {
            return false;
        }

        return $topic->delete();
    }

    private function resolveContext(User $user, $query): array
    {
        $subjectsQuery = Subject::query();
        $classesQuery = SchoolClass::query();

        if (! $user->can('sys:manage_settings')) {
            $school = $user->school_id ? School::find($user->school_id) : null;
            if ($school) {
                $subjectsQuery->where('level', $school->type);
                $classesQuery->where('level', $school->type);
                $query->whereHas('subject', fn ($q) => $q->where('level', $school->type));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return [
            $subjectsQuery->orderBy('name')->get(),
            $classesQuery->orderBy('name')->get(),
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['level'])) {
            $query->whereHas('subject', fn ($q) => $q->where('level', $filters['level']));
        }

        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (! empty($filters['school_class_id'])) {
            $query->where('school_class_id', $filters['school_class_id']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
    }

    private function levelOptions(): Collection
    {
        return collect(ClassLevel::cases())->map(fn ($l) => [
            'value' => $l->value,
            'label' => Str::title($l->value),
        ]);
    }
}
