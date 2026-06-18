<?php

namespace App\Services;

use App\Models\Topic;

class TopicService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Topic
    {
        return Topic::query()->create($this->payload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Topic $topic, array $data): void
    {
        $topic->update($this->payload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'slug' => str($data['name'].'-'.$data['class_level'].'-'.$data['subject_id'])->slug(),
            'subject_id' => $data['subject_id'],
            'class_level' => $data['class_level'],
            'description' => $data['description'] ?? null,
        ];
    }
}
