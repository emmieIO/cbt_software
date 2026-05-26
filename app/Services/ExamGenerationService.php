<?php

namespace App\Services;

use App\Enums\QuestionLevel;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamGenerationService
{
    /**
     * @return array{mcqs: Collection<int, array<string, mixed>>, theory: Collection<int, array<string, mixed>>}
     */
    public function pool(string $subjectId, string $level): array
    {
        $mcqs = Question::query()
            ->where('type', 'multiple_choice')
            ->where('level', $level)
            ->whereHas('topic', fn ($query) => $query->where('subject_id', $subjectId))
            ->with(['options', 'topic'])
            ->orderBy('used_count')
            ->orderBy('last_used_at')
            ->get()
            ->map(fn (Question $question) => [
                'id' => $question->id,
                'content' => $question->content,
                'used_count' => $question->used_count,
                'last_used_at' => $question->last_used_at?->diffForHumans(),
                'topic' => $question->topic?->name,
                'type' => 'mcq',
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'content' => $option->content,
                    'is_correct' => $option->is_correct,
                ]),
            ]);

        $theory = Question::query()
            ->where('type', 'theory')
            ->where('level', $level)
            ->whereHas('topic', fn ($query) => $query->where('subject_id', $subjectId))
            ->with('topic')
            ->orderBy('used_count')
            ->orderBy('last_used_at')
            ->get()
            ->map(fn (Question $question) => [
                'id' => $question->id,
                'content' => $question->content,
                'used_count' => $question->used_count,
                'last_used_at' => $question->last_used_at?->diffForHumans(),
                'topic' => $question->topic?->name,
                'type' => 'theory',
                'marking_scheme' => $question->marking_scheme,
            ]);

        return ['mcqs' => $mcqs, 'theory' => $theory];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function generateFromSelection(array $data, string $createdBy): Exam
    {
        $questions = Question::query()
            ->whereIn('id', $data['question_ids'])
            ->with(['options', 'topic.subject'])
            ->get();

        return DB::transaction(function () use ($data, $createdBy, $questions) {
            [$exam, $mcqs, $theory] = $this->createExamRecord($data, $createdBy, $questions);
            $this->attachQuestions($exam, $mcqs, $theory, true);

            return $exam->fresh(['mcqs.options', 'theoryQuestions']);
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateSelection(Exam $exam, array $data): void
    {
        $questions = Question::query()
            ->whereIn('id', $data['question_ids'])
            ->with(['options', 'topic.subject'])
            ->get();

        DB::transaction(function () use ($exam, $data, $questions): void {
            [$payload, $mcqs, $theory] = $this->examPayloadFromQuestions($data, $questions, $exam->subject_name, $exam->level);

            $exam->update($payload);
            $exam->questions()->sync($this->syncData($mcqs, $theory));
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function generateRandom(array $data, string $createdBy): Exam
    {
        $subject = Subject::query()->findOrFail($data['subject_id']);
        $mcqs = collect();
        $theory = collect();

        if ($data['mcq_count'] > 0) {
            $mcqs = Question::query()
                ->where('type', 'multiple_choice')
                ->where('level', $data['level'])
                ->whereHas('topic', fn ($query) => $query->where('subject_id', $data['subject_id']))
                ->with('options')
                ->inRandomOrder()
                ->take($data['mcq_count'])
                ->get();
        }

        if ($data['theory_count'] > 0) {
            $theory = Question::query()
                ->where('type', 'theory')
                ->where('level', $data['level'])
                ->whereHas('topic', fn ($query) => $query->where('subject_id', $data['subject_id']))
                ->inRandomOrder()
                ->take($data['theory_count'])
                ->get();
        }

        return DB::transaction(function () use ($data, $createdBy, $subject, $mcqs, $theory) {
            $exam = Exam::query()->create([
                'title' => $data['title'],
                'subject_name' => $subject->name,
                'level' => $data['level'],
                'instructions' => $data['instructions'] ?? 'Answer all questions carefully.',
                'mcq_count' => $mcqs->count(),
                'theory_count' => $theory->count(),
                'total_marks' => $mcqs->count() + $theory->sum(fn ($question) => collect($question->marking_scheme)->sum('weight')),
                'created_by' => $createdBy,
            ]);

            $this->attachQuestions($exam, $mcqs->values(), $theory->values(), true);

            return $exam;
        });
    }

    /**
     * @param Collection<int, Question> $questions
     * @return array{0: Exam, 1: Collection<int, Question>, 2: Collection<int, Question>}
     */
    private function createExamRecord(array $data, string $createdBy, Collection $questions): array
    {
        [$payload, $mcqs, $theory] = $this->examPayloadFromQuestions($data, $questions, 'General', 'js');

        $exam = Exam::query()->create([
            ...$payload,
            'created_by' => $createdBy,
        ]);

        return [$exam, $mcqs, $theory];
    }

    /**
     * @param Collection<int, Question> $questions
     * @return array{0: array<string, mixed>, 1: Collection<int, Question>, 2: Collection<int, Question>}
     */
    private function examPayloadFromQuestions(array $data, Collection $questions, string $fallbackSubject, QuestionLevel|string $fallbackLevel): array
    {
        $mcqs = $questions->where('type', 'multiple_choice')->values();
        $theory = $questions->where('type', 'theory')->values();

        $subjectName = $mcqs->first()?->topic?->subject?->name
            ?? $theory->first()?->topic?->subject?->name
            ?? $fallbackSubject;

        $level = $mcqs->first()?->level
            ?? $theory->first()?->level
            ?? $fallbackLevel;

        $levelValue = $level instanceof QuestionLevel ? $level->value : $level;

        return [[
            'title' => $data['title'],
            'subject_name' => $subjectName,
            'level' => $levelValue,
            'instructions' => $data['instructions'] ?? 'Answer all questions carefully.',
            'mcq_count' => $mcqs->count(),
            'theory_count' => $theory->count(),
            'total_marks' => $mcqs->count() + $theory->sum(fn ($question) => collect($question->marking_scheme)->sum('weight')),
        ], $mcqs, $theory];
    }

    /**
     * @param Collection<int, Question> $mcqs
     * @param Collection<int, Question> $theory
     */
    private function attachQuestions(Exam $exam, Collection $mcqs, Collection $theory, bool $markAsUsed): void
    {
        foreach ($mcqs as $index => $question) {
            $exam->questions()->attach($question->id, ['section' => 'mcq', 'sort_order' => $index]);
            if ($markAsUsed) {
                $question->markAsUsed();
            }
        }

        foreach ($theory as $index => $question) {
            $exam->questions()->attach($question->id, ['section' => 'theory', 'sort_order' => $index]);
            if ($markAsUsed) {
                $question->markAsUsed();
            }
        }
    }

    /**
     * @param Collection<int, Question> $mcqs
     * @param Collection<int, Question> $theory
     * @return array<string, array{section: string, sort_order: int}>
     */
    private function syncData(Collection $mcqs, Collection $theory): array
    {
        $syncData = [];

        foreach ($mcqs as $index => $question) {
            $syncData[$question->id] = ['section' => 'mcq', 'sort_order' => $index];
        }

        foreach ($theory as $index => $question) {
            $syncData[$question->id] = ['section' => 'theory', 'sort_order' => $index];
        }

        return $syncData;
    }
}
