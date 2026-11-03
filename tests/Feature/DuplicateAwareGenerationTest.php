<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Jobs\GenerateQuestionsFromKnowledgeJob;
use App\Models\GeneratedQuestionDraft;
use App\Models\Question;
use App\Models\QuestionGenerationRun;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\GeneratedQuestionValidator;
use App\Services\QuestionDuplicateService;
use App\Services\QuestionGenerationContextService;
use App\Services\QuestionGenerationService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['knowledge.generation.duplicate_attempts' => 2]);
});

test('generation excludes earlier bank questions and other teachers drafts and requests replacements', function () {
    [$run, $retrieval] = duplicateGenerationFixture(1);
    $bank = duplicateCandidate('Define factorisation.');
    $previous = duplicateCandidate('What is the degree of a quadratic equation?');
    Question::factory()->create(['topic_id' => $run->topic_id, 'level' => 'ss', 'class_level' => '11', 'type' => 'short_answer', 'content' => $bank['content']]);
    $otherRun = QuestionGenerationRun::factory()->create(['subject_id' => $run->subject_id, 'topic_id' => $run->topic_id, 'class_level' => '11', 'level' => 'ss']);
    $storedDraft = $previous;
    unset($storedDraft['source_chunk_ids']);
    GeneratedQuestionDraft::factory()->for($otherRun, 'run')->create([...$storedDraft, 'source_references' => []]);
    $new = duplicateCandidate('Explain why each factor can be set equal to zero when solving a quadratic equation.');
    QuestionGeneratorAgent::fake([['questions' => [$bank, $previous]], ['questions' => [$new]]])->preventStrayPrompts();

    $result = app(QuestionGenerationService::class)->generate($run, $retrieval);
    expect(array_column($result['questions'], 'content'))->toBe([$new['content']])
        ->and(implode(' ', $result['warnings']))->toContain('Excluded 2 repeated');
    QuestionGeneratorAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, $bank['content'])
        && str_contains($prompt->prompt, $previous['content']));
    expect(Question::query()->count())->toBe(1);
});

test('same batch duplicates are replaced with only the remaining type quota', function () {
    [$run, $retrieval] = duplicateGenerationFixture(2);
    $question = duplicateCandidate('Define factorisation.');
    $replacement = duplicateCandidate('Explain the zero product property.');
    QuestionGeneratorAgent::fake([['questions' => [$question, $question]], ['questions' => [$replacement]]])->preventStrayPrompts();

    $result = app(QuestionGenerationService::class)->generate($run, $retrieval);
    expect(array_column($result['questions'], 'content'))->toBe([$question['content'], $replacement['content']]);
    QuestionGeneratorAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, '"quantity": 1')
        && str_contains($prompt->prompt, '"short_answer": 1') && str_contains($prompt->prompt, $question['content']));
});

test('bounded attempts report a shortfall rather than saving repeated questions', function () {
    [$run, $retrieval] = duplicateGenerationFixture(2);
    $question = duplicateCandidate('Define factorisation.');
    QuestionGeneratorAgent::fake([['questions' => [$question, $question]], ['questions' => [$question]]])->preventStrayPrompts();
    $context = Mockery::mock(QuestionGenerationContextService::class);
    $context->shouldReceive('build')->once()->andReturn($retrieval);

    (new GenerateQuestionsFromKnowledgeJob($run->id))->handle($context, app(QuestionGenerationService::class), app(GeneratedQuestionValidator::class));

    expect($run->refresh()->status)->toBe(QuestionGenerationRun::STATUS_PARTIAL)
        ->and($run->drafts()->count())->toBe(1)
        ->and(implode(' ', $run->warnings))->toContain('1 new valid questions')->toContain('Excluded 2 repeated');
});

test('a failed replacement preserves the first checked questions', function () {
    [$run, $retrieval] = duplicateGenerationFixture(2);
    $question = duplicateCandidate('Define factorisation.');
    $calls = 0;
    QuestionGeneratorAgent::fake(function () use (&$calls, $question) {
        if (++$calls > 1) {
            throw new RuntimeException('Provider unavailable');
        }

        return ['questions' => [$question, $question]];
    });
    $result = app(QuestionGenerationService::class)->generate($run, $retrieval);

    expect($calls)->toBe(2)->and($result['questions'])->toHaveCount(1)
        ->and(implode(' ', $result['warnings']))->toContain('Previously checked questions were preserved');
});

test('duplicate history includes retired and deleted questions without a latest 500 cutoff', function () {
    [$run] = duplicateGenerationFixture(1);
    $attributes = ['topic_id' => $run->topic_id, 'level' => 'ss', 'class_level' => '11'];
    $retired = Question::factory()->create([...$attributes, 'retired_at' => now(), 'content' => 'Define factorisation.']);
    $deleted = Question::factory()->create([...$attributes, 'content' => 'Define a quadratic root.']);
    $deleted->delete();
    Question::factory()->count(501)->create($attributes);
    $service = app(QuestionDuplicateService::class);
    $history = $service->history($run);

    expect($history)->toHaveCount(503)
        ->and($service->findMatch(['content' => $retired->content], $history)['id'])->toBe($retired->id)
        ->and($service->findMatch(['content' => $deleted->content], $history)['id'])->toBe($deleted->id);
});

test('duplicate checks respect subject and class while covering sibling topics', function () {
    [$run] = duplicateGenerationFixture(1);
    $otherTopic = Topic::factory()->create(['subject_id' => $run->subject_id, 'class_level' => '11']);
    $sameClass = Question::factory()->create(['topic_id' => $otherTopic->id, 'level' => 'ss', 'class_level' => '11']);
    Question::factory()->create(['topic_id' => $run->topic_id, 'level' => 'ss', 'class_level' => '12']);
    Question::factory()->create(['level' => 'ss', 'class_level' => '11']);

    expect(array_column(app(QuestionDuplicateService::class)->history($run), 'id'))->toBe([$sameClass->id]);
});

test('concurrent history changes are rechecked before generated drafts are persisted', function () {
    [$run, $retrieval] = duplicateGenerationFixture(1);
    $question = duplicateCandidate('Define factorisation.');
    $context = Mockery::mock(QuestionGenerationContextService::class);
    $context->shouldReceive('build')->andReturn($retrieval);
    $generator = Mockery::mock(QuestionGenerationService::class);
    $generator->shouldReceive('generate')->once()->andReturnUsing(function () use ($run, $question): array {
        Question::factory()->create(['topic_id' => $run->topic_id, 'level' => 'ss', 'class_level' => '11', 'type' => 'short_answer', 'content' => $question['content']]);

        return ['questions' => [$question]];
    });

    (new GenerateQuestionsFromKnowledgeJob($run->id))->handle($context, $generator, app(GeneratedQuestionValidator::class));

    expect($run->refresh()->status)->toBe(QuestionGenerationRun::STATUS_FAILED)
        ->and($run->drafts()->sole()->is_valid)->toBeFalse()
        ->and(Question::query()->count())->toBe(1);
});

/** @return array{QuestionGenerationRun, array<string, mixed>} */
function duplicateGenerationFixture(int $count): array
{
    $subject = Subject::factory()->create(['level' => 'ss']);
    $topic = Topic::factory()->for($subject)->create(['class_level' => '11']);
    $run = QuestionGenerationRun::factory()->create(['requested_by' => User::factory(), 'subject_id' => $subject->id, 'topic_id' => $topic->id,
        'class_level' => '11', 'level' => 'ss', 'requested_count' => $count, 'difficulty' => 'medium',
        'type_distribution' => ['multiple_choice' => 0, 'short_answer' => $count, 'theory' => 0]]);

    return [$run, ['query' => 'Factorisation', 'context' => 'Factorisation rewrites an expression as a product of factors. A quadratic has degree two. When a product is zero, at least one factor is zero.',
        'sources' => [['chunk_id' => 'source-one', 'document_id' => 'document-one', 'content' => 'Factorisation lesson.']]]];
}

/** @return array<string, mixed> */
function duplicateCandidate(string $content): array
{
    return ['type' => 'short_answer', 'difficulty' => 'medium', 'content' => $content, 'expected_answer' => 'An expression written as a product of factors.',
        'options' => [], 'marking_scheme' => [['point' => 'Explains the relevant property.', 'weight' => 2]], 'source_chunk_ids' => ['source-one']];
}
