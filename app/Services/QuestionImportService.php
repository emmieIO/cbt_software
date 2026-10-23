<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\AcademicLevels;
use Illuminate\Support\Facades\DB;

class QuestionImportService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function importRows(array $rows, string $defaultLevel, string $createdBy, string $origin = 'file_import'): int
    {
        $subjectCache = [];
        $topicCache = [];

        return DB::transaction(function () use ($rows, $defaultLevel, $createdBy, $origin, &$subjectCache, &$topicCache) {
            $created = 0;

            foreach ($rows as $row) {
                $level = $this->effectiveLevel($row, $defaultLevel);
                $classLevel = $this->effectiveClassLevel($row, $level);
                $subjectKey = strtolower($row['subject_name']).'::'.$level;

                if (! isset($subjectCache[$subjectKey])) {
                    $subjectCache[$subjectKey] = Subject::firstOrCreate(
                        ['name' => $row['subject_name'], 'level' => $level],
                        ['slug' => str($row['subject_name'].'-'.$level)->slug()]
                    )->id;
                }

                $topicKey = $subjectKey.'::'.$classLevel.'::'.strtolower($row['topic_name']);
                if (! isset($topicCache[$topicKey])) {
                    $topicCache[$topicKey] = Topic::firstOrCreate(
                        ['name' => $row['topic_name'], 'subject_id' => $subjectCache[$subjectKey], 'class_level' => $classLevel],
                        ['slug' => str($row['topic_name'].'-'.$classLevel.'-'.$subjectCache[$subjectKey])->slug()]
                    )->id;
                }

                $this->importResolvedRow($row, $topicCache[$topicKey], $level, $classLevel, $createdBy, $origin);

                $created++;
            }

            return $created;
        });
    }

    /** @param array<string, mixed> $row */
    public function importResolvedRow(array $row, string $topicId, string $level, string $classLevel, string $createdBy, string $origin): Question
    {
        return DB::transaction(function () use ($row, $topicId, $level, $classLevel, $createdBy, $origin): Question {
            $question = Question::query()->create([
                'topic_id' => $topicId, 'content' => $row['content'], 'image_path' => $row['image_url'] ?? null,
                'explanation' => $row['explanation'] ?? null, 'type' => $row['type'], 'level' => $level, 'class_level' => $classLevel,
                'marking_scheme' => in_array($row['type'], ['short_answer', 'theory'], true) ? ($row['marking_scheme'] ?? []) : null,
                'created_by' => $createdBy, 'origin' => $origin, 'difficulty' => $row['difficulty'] ?? null,
                'expected_answer' => $row['expected_answer'] ?? null, 'subtopic_id' => $row['subtopic_id'] ?? null,
                'generated_draft_id' => $row['generated_draft_id'] ?? null,
            ]);
            if ($row['type'] === 'multiple_choice') {
                $this->createOptions($question, $row);
            }

            return $question;
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function effectiveLevel(array $row, string $defaultLevel): string
    {
        $level = strtolower(trim((string) ($row['level'] ?? $defaultLevel)));

        return in_array($level, ['lp', 'hp', 'js', 'ss'], true) ? $level : $defaultLevel;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function effectiveClassLevel(array $row, string $level): string
    {
        $classLevel = trim((string) ($row['class_level'] ?? ''));

        return AcademicLevels::classBelongsToLevel($classLevel, $level)
            ? $classLevel
            : AcademicLevels::defaultClassFor($level);
    }

    /**
     * @param  array<string, mixed>  $row
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
