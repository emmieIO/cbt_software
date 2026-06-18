<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, string $createdBy): Question
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $question = Question::query()->create([
                'topic_id' => $data['topic_id'],
                'content' => $data['content'],
                'type' => $data['type'],
                'level' => $data['level'],
                'class_level' => $data['class_level'],
                'explanation' => $data['explanation'] ?? null,
                'image_path' => $this->storeImage($data['image'] ?? null),
                'marking_scheme' => $this->usesMarkingScheme($data['type']) ? ($data['marking_scheme'] ?? []) : null,
                'created_by' => $createdBy,
            ]);

            $this->syncOptions($question, $data['type'], $data['options'] ?? []);

            return $question;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function bulkCreate(array $rows, string $createdBy): int
    {
        return DB::transaction(function () use ($rows, $createdBy) {
            foreach ($rows as $row) {
                $question = Question::query()->create([
                    'topic_id' => $row['topic_id'],
                    'content' => $row['content'],
                    'type' => $row['type'],
                    'level' => $row['level'],
                    'class_level' => $row['class_level'],
                    'marking_scheme' => $this->usesMarkingScheme($row['type']) ? ($row['marking_scheme'] ?? []) : null,
                    'created_by' => $createdBy,
                ]);

                $this->syncOptions($question, $row['type'], $row['options'] ?? []);
            }

            return count($rows);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Question $question, array $data): void
    {
        DB::transaction(function () use ($question, $data): void {
            $imagePath = $question->image_path;

            if (($data['image'] ?? null) instanceof UploadedFile) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $this->storeImage($data['image']);
            } elseif (! empty($data['remove_image'])) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = null;
            }

            $question->update([
                'topic_id' => $data['topic_id'],
                'content' => $data['content'],
                'type' => $data['type'],
                'level' => $data['level'],
                'class_level' => $data['class_level'],
                'explanation' => $data['explanation'] ?? null,
                'image_path' => $imagePath,
                'marking_scheme' => $this->usesMarkingScheme($data['type']) ? ($data['marking_scheme'] ?? []) : null,
            ]);

            $question->options()->delete();
            $this->syncOptions($question, $data['type'], $data['options'] ?? []);
        });
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        return $image?->store('questions', 'public');
    }

    private function usesMarkingScheme(string $type): bool
    {
        return in_array($type, ['short_answer', 'theory'], true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(Question $question, string $type, array $options): void
    {
        if ($type !== 'multiple_choice') {
            return;
        }

        foreach ($options as $option) {
            $question->options()->create([
                'content' => $option['content'],
                'is_correct' => $option['is_correct'] ?? false,
            ]);
        }
    }
}
