<?php

namespace App\Services;

use App\Models\Subject;

class SubjectService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Subject
    {
        return Subject::query()->create($this->payload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subject $subject, array $data): void
    {
        $subject->update($this->payload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'slug' => str($data['name'])->slug(),
            'description' => $data['description'] ?? null,
            'level' => $data['level'],
        ];
    }
}
