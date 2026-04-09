<?php

namespace App\Services\Question;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\User;
use App\Services\QuestionService;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuestionPayloadService
{
    public function __construct(protected QuestionService $questionService) {}

    private function canAuthorAcrossLevels(User $user): bool
    {
        return $user->can('access:cross-level-authoring')
            || $user->can('bank:create_cross_level');
    }

    /**
     * Build payload for the Question Bank index page.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getIndexPayload(User $user, array $filters): array
    {
        $context = $this->questionService->getAuthorizedContext(
            $user,
            false,
            $this->canAuthorAcrossLevels($user)
        );

        return [
            'questions' => $this->questionService->getFilteredQuestions($filters, $user),
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
            'types' => $this->typeOptions(),
            'difficulties' => $this->difficultyOptions(),
            'levels' => $this->levelOptions($context['classes']),
        ];
    }

    /**
     * Build shared payload for create/generate/batch question forms.
     */
    public function getFormPayload(User $user, bool $withTopics = true): array
    {
        $context = $this->questionService->getAuthorizedContext(
            $user,
            $withTopics,
            $this->canAuthorAcrossLevels($user)
        );

        return [
            'subjects' => $context['subjects'],
            'classes' => $context['classes'],
            'types' => $this->typeOptions(),
            'difficulties' => $this->difficultyOptions(),
        ];
    }

    /**
     * Build payload for the question edit page.
     */
    public function getEditPayload(User $user, Question $question): array
    {
        $question->load(['topic.subject', 'options']);

        return [
            'question' => $question,
            ...$this->getFormPayload($user, true),
        ];
    }

    private function typeOptions(): Collection
    {
        return collect(QuestionType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'label' => str_replace('_', ' ', Str::title($t->value))]);
    }

    private function difficultyOptions(): Collection
    {
        return collect(QuestionDifficulty::cases())
            ->map(fn ($d) => ['value' => $d->value, 'label' => Str::title($d->value)]);
    }

    private function levelOptions(Collection $classes): Collection
    {
        return $classes->pluck('level')->unique()->values()->map(function ($level) {
            $value = $level instanceof BackedEnum ? $level->value : $level;

            return [
                'value' => $value,
                'label' => Str::title($value),
            ];
        });
    }
}
