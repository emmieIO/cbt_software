<?php

namespace App\Services;

use App\Models\QuestionGenerationRun;
use App\Support\MathContent;
use App\Support\RichContent;

class GeneratedQuestionValidator
{
    private const TYPES = ['multiple_choice', 'short_answer', 'theory'];

    private const DIFFICULTIES = ['easy', 'medium', 'hard'];

    /**
     * @param  array<int, mixed>  $questions
     * @param  array<int, array<string, mixed>>  $sources
     * @return array{drafts: array<int, array<string, mixed>>, errors: array<int, string>, warnings: array<int, string>}
     */
    public function validate(QuestionGenerationRun $run, array $questions, array $sources): array
    {
        $sourceMap = collect($sources)->keyBy('chunk_id');
        $drafts = [];
        $errors = [];
        $warnings = [];
        $seen = [];

        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                $errors[] = 'Generated item '.($index + 1).' was not a question object.';

                continue;
            }

            $type = (string) ($question['type'] ?? '');
            $difficulty = (string) ($question['difficulty'] ?? '');
            $content = $this->clean((string) ($question['content'] ?? ''));
            $draftErrors = [];
            $draftWarnings = [];

            if (! in_array($type, self::TYPES, true)) {
                $draftErrors[] = 'Question type is invalid.';
            }
            if (! in_array($difficulty, self::DIFFICULTIES, true)) {
                $draftErrors[] = 'Difficulty is invalid.';
            }
            if ($run->difficulty !== 'mixed' && $difficulty !== $run->difficulty) {
                $draftErrors[] = 'Difficulty does not match the generation request.';
            }
            if ($content === '') {
                $draftErrors[] = 'Question content is required.';
            }

            $duplicateKey = mb_strtolower(preg_replace('/[^\pL\pN]+/u', '', RichContent::text($content)) ?? '');
            if ($duplicateKey !== '' && $this->isDuplicate($duplicateKey, $seen)) {
                $draftErrors[] = 'Question duplicates another generated draft.';
            }
            $seen[] = $duplicateKey;

            $options = $this->cleanOptions($question['options'] ?? []);
            $markingScheme = $this->cleanMarkingScheme($question['marking_scheme'] ?? []);
            $rawMarkingScheme = is_array($question['marking_scheme'] ?? null) ? $question['marking_scheme'] : [];
            $expectedAnswer = $this->clean((string) ($question['expected_answer'] ?? '')) ?: null;
            $explanation = $this->clean((string) ($question['explanation'] ?? '')) ?: null;

            if ($type === 'multiple_choice') {
                if (count($options) !== 4) {
                    $draftErrors[] = 'Multiple-choice questions must have exactly four options.';
                }
                if (collect($options)->where('is_correct', true)->count() !== 1) {
                    $draftErrors[] = 'Multiple-choice questions must have exactly one correct option.';
                }
                $markingScheme = [];
            } elseif (in_array($type, ['short_answer', 'theory'], true)) {
                if ($expectedAnswer === null) {
                    $draftErrors[] = 'Written questions require an expected answer.';
                }
                if ($markingScheme === []) {
                    $draftErrors[] = 'Written questions require a marking scheme.';
                }
                if (count($rawMarkingScheme) !== count($markingScheme)) {
                    $draftErrors[] = 'Every marking point must contain text and a positive integer weight.';
                }
                $options = [];
            }

            $requestedSourceIds = array_values(array_unique(array_filter(
                is_array($question['source_chunk_ids'] ?? null) ? $question['source_chunk_ids'] : [],
                'is_string',
            )));
            $unknownSourceIds = array_values(array_diff($requestedSourceIds, $sourceMap->keys()->all()));
            if ($requestedSourceIds === []) {
                $draftErrors[] = 'At least one source citation is required.';
            }
            if ($unknownSourceIds !== []) {
                $draftErrors[] = 'One or more source citations were not supplied to the generator.';
            }
            $draftSources = collect($requestedSourceIds)
                ->filter(fn (string $id): bool => $sourceMap->has($id))
                ->map(fn (string $id): array => $sourceMap->get($id))
                ->values()
                ->all();

            if ($this->containsMath($content, $expectedAnswer, $options, $markingScheme)) {
                $draftWarnings[] = 'Mathematical content requires teacher verification for notation and correctness.';
            }

            $drafts[] = [
                'sequence' => $index + 1,
                'type' => in_array($type, self::TYPES, true) ? $type : 'short_answer',
                'difficulty' => in_array($difficulty, self::DIFFICULTIES, true) ? $difficulty : 'medium',
                'content' => $content ?: 'Invalid generated question',
                'explanation' => $explanation,
                'expected_answer' => $expectedAnswer,
                'options' => $options ?: null,
                'marking_scheme' => $markingScheme ?: null,
                'source_references' => $draftSources,
                'validation_errors' => $draftErrors,
                'warnings' => $draftWarnings,
                'is_valid' => $draftErrors === [],
            ];
        }

        if (count($drafts) !== $run->requested_count) {
            $errors[] = "Requested {$run->requested_count} questions, but the generator returned ".count($drafts).'.';
        }

        foreach (self::TYPES as $type) {
            $expected = (int) ($run->type_distribution[$type] ?? 0);
            $actual = collect($drafts)->where('type', $type)->count();
            if ($actual !== $expected) {
                $errors[] = "Expected {$expected} {$type} questions, but received {$actual}.";
            }
        }

        return compact('drafts', 'errors', 'warnings');
    }

    private function clean(string $content): string
    {
        return RichContent::sanitize(MathContent::normalize($content));
    }

    /** @return array<int, array{content: string, is_correct: bool}> */
    private function cleanOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        return collect($options)->filter(fn (mixed $option): bool => is_array($option))->map(fn (array $option): array => [
            'content' => $this->clean((string) ($option['content'] ?? '')),
            'is_correct' => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOL),
        ])->filter(fn (array $option): bool => $option['content'] !== '')->values()->all();
    }

    /** @return array<int, array{point: string, weight: int}> */
    private function cleanMarkingScheme(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)->filter(fn (mixed $item): bool => is_array($item))->map(fn (array $item): array => [
            'point' => $this->clean((string) ($item['point'] ?? '')),
            'weight' => (int) ($item['weight'] ?? 0),
        ])->filter(fn (array $item): bool => $item['point'] !== '' && $item['weight'] > 0)->values()->all();
    }

    /**
     * @param  array<int, array{content: string, is_correct: bool}>  $options
     * @param  array<int, array{point: string, weight: int}>  $markingScheme
     */
    private function containsMath(string $content, ?string $answer, array $options, array $markingScheme): bool
    {
        $text = implode(' ', [
            $content,
            $answer ?? '',
            ...array_column($options, 'content'),
            ...array_column($markingScheme, 'point'),
        ]);

        return preg_match('/(data-latex|\\\\\(|\\\\\[|[=±×÷√]|\b\d+[a-z]\b|\^\{?\d)/iu', $text) === 1;
    }

    /** @param array<int, string> $seen */
    private function isDuplicate(string $candidate, array $seen): bool
    {
        foreach ($seen as $existing) {
            if ($candidate === $existing) {
                return true;
            }

            similar_text($candidate, $existing, $similarity);
            if ($similarity >= 92.0) {
                return true;
            }
        }

        return false;
    }
}
