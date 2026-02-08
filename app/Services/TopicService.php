<?php

namespace App\Services;

use App\DTOs\TopicDTO;
use App\Models\Topic;
use Illuminate\Support\Str;

class TopicService
{
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
}
