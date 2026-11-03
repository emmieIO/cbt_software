<?php

namespace App\Services;

use App\Enums\QuestionLevel;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamGenerationService
{
    private const WRITTEN_TYPES = ['short_answer', 'theory'];

    /**
     * @return array{mcqs: Collection<int, array<string, mixed>>, theory: Collection<int, array<string, mixed>>}
     */
    public function pool(string $subjectId, string $level, string $classLevel, ?Exam $exam = null): array
    {
        $retainedRetiredIds = $exam?->questions()->retired()->pluck('questions.id')->all() ?? [];
        $pool = Question::query()
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->active()
                    ->where('level', $level)
                    ->where(fn (Builder $query) => $query->where('class_level', $classLevel)->orWhereNull('class_level'))
                    ->whereHas('topic', fn (Builder $query) => $query->where('subject_id', $subjectId)))
                ->orWhereIn('id', $retainedRetiredIds));

        $mcqs = (clone $pool)
            ->where('type', 'multiple_choice')
            ->with(['options', 'topic'])
            ->orderBy('used_count')
            ->orderBy('last_used_at')
            ->get()
            ->map(fn (Question $question) => [
                'id' => $question->id,
                'content' => $question->content,
                'used_count' => $question->used_count,
                'retired_at' => $question->retired_at?->toISOString(),
                'last_used_at' => $question->last_used_at?->diffForHumans(),
                'topic_id' => $question->topic_id,
                'topic' => $question->topic?->name,
                'type' => 'mcq',
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'content' => $option->content,
                    'is_correct' => $option->is_correct,
                ]),
            ]);

        $theory = (clone $pool)
            ->whereIn('type', self::WRITTEN_TYPES)
            ->with('topic')
            ->orderBy('used_count')
            ->orderBy('last_used_at')
            ->get()
            ->map(fn (Question $question) => [
                'id' => $question->id,
                'content' => $question->content,
                'used_count' => $question->used_count,
                'retired_at' => $question->retired_at?->toISOString(),
                'last_used_at' => $question->last_used_at?->diffForHumans(),
                'topic_id' => $question->topic_id,
                'topic' => $question->topic?->name,
                'type' => $question->type->value,
                'marking_scheme' => $question->marking_scheme,
            ]);

        return ['mcqs' => $mcqs, 'theory' => $theory];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function generateFromSelection(array $data, string $createdBy): Exam
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $questions = $this->selectedQuestions($data['question_ids']);
            [$exam, $mcqs, $theory] = $this->createExamRecord($data, $createdBy, $questions);
            $this->attachQuestions($exam, $mcqs, $theory, true);

            return $exam->fresh(['mcqs.options', 'theoryQuestions']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSelection(Exam $exam, array $data): void
    {
        DB::transaction(function () use ($exam, $data): void {
            $exam = Exam::query()->lockForUpdate()->findOrFail($exam->id);
            $questions = $this->selectedQuestions($data['question_ids'], $exam);
            [$payload, $mcqs, $theory] = $this->examPayloadFromQuestions($data, $questions, $exam->subject_name, $exam->level, $exam->class_level);

            $exam->update($payload);
            $changes = $exam->questions()->sync($this->syncData($mcqs, $theory));

            foreach ($questions->whereIn('id', $changes['attached']) as $question) {
                $question->markAsUsed();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function generateRandom(array $data, string $createdBy): Exam
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $subject = Subject::query()->findOrFail($data['subject_id']);
            $mcqs = collect();
            $theory = collect();

            if ($data['mcq_count'] > 0) {
                $mcqs = Question::query()
                    ->active()
                    ->where('type', 'multiple_choice')
                    ->where('level', $data['level'])
                    ->where(function ($query) use ($data): void {
                        $query->where('class_level', $data['class_level'])->orWhereNull('class_level');
                    })
                    ->whereHas('topic', fn ($query) => $query->where('subject_id', $data['subject_id']))
                    ->with('options')
                    ->inRandomOrder()
                    ->take($data['mcq_count'])
                    ->lockForUpdate()
                    ->get();
            }

            if ($data['theory_count'] > 0) {
                $theory = Question::query()
                    ->active()
                    ->whereIn('type', self::WRITTEN_TYPES)
                    ->where('level', $data['level'])
                    ->where(function ($query) use ($data): void {
                        $query->where('class_level', $data['class_level'])->orWhereNull('class_level');
                    })
                    ->whereHas('topic', fn ($query) => $query->where('subject_id', $data['subject_id']))
                    ->inRandomOrder()
                    ->take($data['theory_count'])
                    ->lockForUpdate()
                    ->get();
            }

            $exam = Exam::query()->create([
                'title' => $data['title'],
                'academic_session_id' => $data['academic_session_id'],
                'subject_name' => $subject->name,
                'level' => $data['level'],
                'class_level' => $data['class_level'],
                'instructions' => $data['instructions'] ?? 'Answer all questions carefully.',
                'duration' => $data['duration'] ?? null,
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
     * @param  array<int, string>  $questionIds
     * @return Collection<int, Question>
     */
    private function selectedQuestions(array $questionIds, ?Exam $exam = null): Collection
    {
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->with(['options', 'topic.subject'])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $retainedQuestionIds = $exam?->questions()->pluck('questions.id')->all() ?? [];

        foreach ($questionIds as $index => $questionId) {
            $question = $questions->get($questionId);

            if (! $question || ($question->retired_at !== null && ! in_array($questionId, $retainedQuestionIds, true))) {
                throw ValidationException::withMessages([
                    'question_ids.'.$index => 'Select an active question or retain a question already attached to this exam.',
                ]);
            }
        }

        return $questions->values();
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array{0: Exam, 1: Collection<int, Question>, 2: Collection<int, Question>}
     */
    private function createExamRecord(array $data, string $createdBy, Collection $questions): array
    {
        [$payload, $mcqs, $theory] = $this->examPayloadFromQuestions($data, $questions, 'General', 'js', null);

        $exam = Exam::query()->create([
            ...$payload,
            'created_by' => $createdBy,
        ]);

        return [$exam, $mcqs, $theory];
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array{0: array<string, mixed>, 1: Collection<int, Question>, 2: Collection<int, Question>}
     */
    private function examPayloadFromQuestions(array $data, Collection $questions, string $fallbackSubject, QuestionLevel|string $fallbackLevel, ?string $fallbackClassLevel): array
    {
        $mcqs = $questions->where('type', 'multiple_choice')->values();
        $theory = $questions->filter(fn (Question $question) => in_array($question->type->value, self::WRITTEN_TYPES, true))->values();

        $subjectName = $mcqs->first()?->topic?->subject?->name
            ?? $theory->first()?->topic?->subject?->name
            ?? $fallbackSubject;

        $level = $mcqs->first()?->level
            ?? $theory->first()?->level
            ?? $fallbackLevel;

        $levelValue = $level instanceof QuestionLevel ? $level->value : $level;
        $classLevel = $mcqs->first()?->class_level
            ?? $theory->first()?->class_level
            ?? $data['class_level']
            ?? $fallbackClassLevel;

        return [[
            'title' => $data['title'],
            'academic_session_id' => $data['academic_session_id'],
            'subject_name' => $subjectName,
            'level' => $levelValue,
            'class_level' => $classLevel,
            'instructions' => $data['instructions'] ?? 'Answer all questions carefully.',
            'duration' => $data['duration'] ?? null,
            'mcq_count' => $mcqs->count(),
            'theory_count' => $theory->count(),
            'total_marks' => $mcqs->count() + $theory->sum(fn ($question) => collect($question->marking_scheme)->sum('weight')),
        ], $mcqs, $theory];
    }

    /**
     * @param  Collection<int, Question>  $mcqs
     * @param  Collection<int, Question>  $theory
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
     * @param  Collection<int, Question>  $mcqs
     * @param  Collection<int, Question>  $theory
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
