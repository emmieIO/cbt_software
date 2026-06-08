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
                $level = $this->effectiveLevel($row, $defaultLevel);
                $subjectKey = strtolower($row['subject_name']).'::'.$level;

                if (! isset($subjectCache[$subjectKey])) {
                    $subjectCache[$subjectKey] = Subject::firstOrCreate(
                        ['name' => $row['subject_name'], 'level' => $level],
                        ['slug' => str($row['subject_name'].'-'.$level)->slug()]
                    )->id;
                }

                $topicKey = $subjectKey.'::'.strtolower($row['topic_name']);
                if (! isset($topicCache[$topicKey])) {
                    $topicCache[$topicKey] = Topic::firstOrCreate(
                        ['name' => $row['topic_name'], 'subject_id' => $subjectCache[$subjectKey]],
                        ['slug' => str($row['topic_name'])->slug()]
                    )->id;
                }

                $question = Question::query()->create([
                    'topic_id' => $topicCache[$topicKey],
                    'content' => $row['content'],
                    'image_path' => $row['image_url'] ?? null,
                    'explanation' => $row['explanation'] ?? null,
                    'type' => $row['type'],
                    'level' => $level,
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
    private function effectiveLevel(array $row, string $defaultLevel): string
    {
        $level = strtolower(trim((string) ($row['level'] ?? $defaultLevel)));

        return in_array($level, ['lp', 'hp', 'js', 'ss'], true) ? $level : $defaultLevel;
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
