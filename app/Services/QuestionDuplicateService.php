<?php

namespace App\Services;

use App\Models\GeneratedQuestionDraft;
use App\Models\Question;
use App\Models\QuestionGenerationRun;
use App\Support\RichContent;

class QuestionDuplicateService
{
    /** @return array<int, array<string, mixed>> */
    public function history(QuestionGenerationRun $run, ?string $ignoreDraftId = null): array
    {
        $history = [];
        // Include retired/deleted bank questions so weekly uploads do not recreate them.
        $questions = Question::withTrashed()->with('options:id,question_id,content')
            ->whereHas('topic', fn ($query) => $query->where('subject_id', $run->subject_id))
            ->where('level', $run->level)
            ->where(fn ($query) => $query->where('class_level', $run->class_level)->orWhereNull('class_level'));
        foreach ($questions->lazyById(200) as $question) {
            $history[] = ['kind' => 'question', 'id' => $question->id, 'content' => $question->content,
                'type' => $question->type->value, 'options' => $question->options->toArray()];
        }

        $drafts = GeneratedQuestionDraft::query()->whereNull('question_id')->where('is_valid', true)
            ->where('review_status', '!=', 'rejected')
            ->when($ignoreDraftId, fn ($query) => $query->whereKeyNot($ignoreDraftId))
            ->whereHas('run', fn ($query) => $query->where('subject_id', $run->subject_id)
                ->where('level', $run->level)->where('class_level', $run->class_level));
        foreach ($drafts->lazyById(200) as $draft) {
            $history[] = ['kind' => 'draft', 'id' => $draft->id, 'content' => $draft->content,
                'type' => $draft->type, 'options' => $draft->options ?? []];
        }

        return $history;
    }

    /** @param array<string, mixed> $candidate @param array<int, array<string, mixed>> $history @return array<string, mixed>|null */
    public function findMatch(array $candidate, array $history): ?array
    {
        foreach ($history as $existing) {
            if ($this->matches($candidate, $existing)) {
                return $existing;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $candidate @param array<string, mixed> $existing */
    public function matches(array $candidate, array $existing): bool
    {
        $left = $this->normalize((string) ($candidate['content'] ?? ''));
        $right = $this->normalize((string) ($existing['content'] ?? ''));
        if ($left === '' || $right === '') {
            return false;
        }

        $leftOptions = $this->options($candidate);
        $rightOptions = $this->options($existing);
        $optionDependent = preg_match('/^(?:which (?:of (?:these|the following)|statement)|choose the (?:correct|incorrect) (?:answer|option))(?: is (?:not )?(?:correct|incorrect|true|false))?$/u', $left) === 1;
        if ($optionDependent && $leftOptions !== [] && $rightOptions !== [] && $leftOptions !== $rightOptions) {
            return false;
        }
        if ($left === $right) {
            return true;
        }

        // Numbers, operators and negation change what is being asked, even in near-identical prose.
        if ($this->significantTerms($left) !== $this->significantTerms($right)) {
            return false;
        }
        if (max(strlen($left), strlen($right)) > 4000) {
            return false;
        }

        $leftWords = array_unique(explode(' ', $left));
        $rightWords = array_unique(explode(' ', $right));
        $union = count(array_unique([...$leftWords, ...$rightWords]));
        $overlap = count(array_intersect($leftWords, $rightWords)) / max(1, $union);
        $similarity = 1 - levenshtein($left, $right) / max(strlen($left), strlen($right));

        return $overlap >= 0.85 || $similarity >= 0.92;
    }

    public function normalize(string $content): string
    {
        $content = preg_replace('/<sup\b[^>]*>(.*?)<\/sup>/is', '^{$1}', $content) ?? $content;
        $content = preg_replace('/<sub\b[^>]*>(.*?)<\/sub>/is', '_{$1}', $content) ?? $content;
        $text = RichContent::text($content);
        $text = preg_replace('/^\s*(?:question\s+)?\d+[.):]\s+/iu', '', $text) ?? $text;
        $text = str_replace(['\\(', '\\)', '\\[', '\\]', '$'], '', $text);
        $text = preg_replace('/(?<!\d)\.|\.(?!\d)|[!?,;:"\x{2018}\x{2019}\x{201C}\x{201D}\x{0027}]/u', ' ', $text) ?? $text;

        return trim(mb_strtolower(preg_replace('/\s+/u', ' ', $text) ?? $text));
    }

    /** @param array<string, mixed> $question @return array<int, string> */
    private function options(array $question): array
    {
        $options = [];
        foreach (is_array($question['options'] ?? null) ? $question['options'] : [] as $option) {
            $options[] = $this->normalize(is_array($option) ? (string) ($option['content'] ?? '') : (string) $option);
        }
        sort($options);

        return $options;
    }

    /** @return array<int, string> */
    private function significantTerms(string $text): array
    {
        preg_match_all('/\d+(?:\.\d+)?|[+\-−=<>≤≥≠^²³×÷\/]|\b(?:not|never|except|incorrect|true|false|least|most|advantages|disadvantages)\b/u', $text, $matches);

        return $matches[0];
    }
}
