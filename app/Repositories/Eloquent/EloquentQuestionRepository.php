<?php

namespace App\Repositories\Eloquent;

use App\Models\Question;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentQuestionRepository implements QuestionRepositoryInterface
{
    public function getPaginatedQuestions(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return Question::query()
            ->with(['topic.subject', 'schoolClass', 'options'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('content', 'like', "%{$search}%");
            })
            ->when($filters['subject_id'] ?? null, function ($query, $subjectId) {
                $query->whereHas('topic', fn ($q) => $q->where('subject_id', $subjectId));
            })
            ->when($filters['topic_id'] ?? null, fn ($q, $topicId) => $q->where('topic_id', $topicId))
            ->when($filters['school_class_id'] ?? null, fn ($q, $classId) => $q->where('school_class_id', $classId))
            ->when($filters['difficulty'] ?? null, fn ($q, $difficulty) => $q->where('difficulty', $difficulty))
            ->when($filters['level'] ?? null, function ($query, $level) {
                $query->whereHas('schoolClass', fn ($q) => $q->where('level', $level));
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(string $id): ?Question
    {
        return Question::with(['topic.subject', 'schoolClass', 'options'])->find($id);
    }

    public function create(array $data, array $options): Question
    {
        return DB::transaction(function () use ($data, $options) {
            $question = Question::create($data);

            foreach ($options as $option) {
                $question->options()->create($option);
            }

            return $question->load('options');
        });
    }

    public function update(string $id, array $data, array $options = []): Question
    {
        return DB::transaction(function () use ($id, $data, $options) {
            $question = Question::findOrFail($id);
            $question->update($data);

            if (! empty($options)) {
                $question->options()->delete();
                foreach ($options as $option) {
                    $question->options()->create($option);
                }
            }

            return $question->load('options');
        });
    }

    public function delete(string $id): bool
    {
        return Question::findOrFail($id)->delete();
    }

    public function getForComposition(string $topicId, string $difficulty, int $count): Collection
    {
        return Question::query()
            ->where('topic_id', $topicId)
            ->where('difficulty', $difficulty)
            ->inRandomOrder()
            ->limit($count)
            ->get();
    }

    public function bulkCreate(array $questions): int
    {
        return DB::transaction(function () use ($questions) {
            $count = 0;
            foreach ($questions as $qData) {
                $options = $qData['options'] ?? [];
                unset($qData['options']);

                $question = Question::create($qData);
                if (! empty($options)) {
                    $question->options()->createMany($options);
                }
                $count++;
            }

            return $count;
        });
    }
}
