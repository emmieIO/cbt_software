<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

class QuestionImportService
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function importRows(array $rows, string $defaultLevel, string $createdBy): int
    {
        $subjectCache = [];
        $topicCache = [];

        return DB::transaction(function () use ($rows, $defaultLevel, $createdBy, &$subjectCache, &$topicCache) {
            $created = 0;

            foreach ($rows as $row) {
                if (! isset($subjectCache[$row['subject_name']])) {
                    $subjectCache[$row['subject_name']] = Subject::firstOrCreate(
                        ['name' => $row['subject_name']],
                        ['slug' => str($row['subject_name'])->slug(), 'level' => $defaultLevel]
                    )->id;
                }

                $topicKey = $row['subject_name'].'::'.$row['topic_name'];
                if (! isset($topicCache[$topicKey])) {
                    $topicCache[$topicKey] = Topic::firstOrCreate(
                        ['name' => $row['topic_name'], 'subject_id' => $subjectCache[$row['subject_name']]],
                        ['slug' => str($row['topic_name'])->slug()]
                    )->id;
                }

                $question = Question::query()->create([
                    'topic_id' => $topicCache[$topicKey],
                    'content' => $row['content'],
                    'image_path' => $row['image_url'] ?? null,
                    'explanation' => $row['explanation'] ?? null,
                    'type' => $row['type'],
                    'level' => $row['level'] ?? $defaultLevel,
                    'marking_scheme' => $row['type'] === 'theory' ? ($row['marking_scheme'] ?? []) : null,
                    'created_by' => $createdBy,
                ]);

                if ($row['type'] === 'multiple_choice') {
                    $this->createOptions($question, $row);
                }

                $created++;
            }

            return $created;
        });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createOptions(Question $question, array $row): void
    {
        $letters = ['a', 'b', 'c', 'd'];

        foreach ($row['options'] as $index => $option) {
            $question->options()->create([
                'content' => $option,
                'is_correct' => $letters[$index] === strtolower($row['correct_answer'] ?? ''),
            ]);
        }
    }
}
