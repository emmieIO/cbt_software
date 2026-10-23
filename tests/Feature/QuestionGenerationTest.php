<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Jobs\GenerateQuestionsFromKnowledgeJob;
use App\Models\GeneratedQuestionDraft;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\Question;
use App\Models\QuestionGenerationRun;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\CurriculumMappingService;
use App\Services\GeneratedQuestionValidator;
use App\Services\QuestionGenerationContextService;
use App\Services\QuestionGenerationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('knowledge.embeddings.provider', 'ollama');
    config()->set('knowledge.embeddings.base_url', 'http://ollama.test:11434');
    config()->set('knowledge.embeddings.model', 'embeddinggemma-2');
    config()->set('knowledge.embeddings.dimensions', 768);
    config()->set('knowledge.retrieval.similarity_threshold', 0.65);
    config()->set('knowledge.generation.minimum_similarity', 0.65);
    config()->set('knowledge.generation.retrieval_limit', 12);
    config()->set('knowledge.generation.context_token_budget', 6000);
});

test('question generation is permission protected and stores a queued run without touching questions', function () {
    Queue::fake();
    [$subject, $topic] = generationCurriculum();
    $unauthorized = User::factory()->create();
    $teacher = generationTeacher();
    $payload = generationRequest($subject, $topic);
    $document = generationReadyDocument('Mapped lesson note');
    generationConfirmCoverage($topic, generationEmbeddedChunk($document, 'Factorisation writes a quadratic expression as factors.', 1.0));

    $this->actingAs($unauthorized)->get(route('question-generations.index'))->assertForbidden();
    $this->actingAs($unauthorized)->post(route('question-generations.store'), $payload)->assertForbidden();

    $response = $this->actingAs($teacher)->post(route('question-generations.store'), $payload);
    $run = QuestionGenerationRun::query()->sole();

    $response->assertRedirect(route('question-generations.show', $run));
    expect($run)->requested_by->toBe($teacher->id)
        ->status->toBe(QuestionGenerationRun::STATUS_PENDING)
        ->and(Question::query()->count())->toBe(0);
    Queue::assertPushed(GenerateQuestionsFromKnowledgeJob::class, fn (GenerateQuestionsFromKnowledgeJob $job): bool => $job->runId === $run->id);
});

test('generation request validates curriculum and exact type totals', function () {
    Queue::fake();
    [$subject, $topic] = generationCurriculum();
    $teacher = generationTeacher();

    $this->actingAs($teacher)->post(route('question-generations.store'), [
        ...generationRequest($subject, $topic),
        'class_level' => '10',
        'quantity' => 4,
    ])->assertSessionHasErrors(['type_distribution']);

    $otherTopic = Topic::factory()->create(['class_level' => '11']);
    $this->actingAs($teacher)->post(route('question-generations.store'), [
        ...generationRequest($subject, $topic),
        'topic_id' => $otherTopic->id,
    ])->assertSessionHasErrors(['topic_id']);
    Queue::assertNothingPushed();
});

test('the job retrieves multiple approved documents excludes unapproved content and persists grounded drafts', function () {
    Http::fake(['ollama.test:11434/api/embed' => Http::response(['embeddings' => [generationVector(1.0)]])]);
    [$subject, $topic] = generationCurriculum();
    $run = generationRun(generationTeacher(), $subject, $topic);
    $lesson = generationReadyDocument('Quadratic Equations Lesson Note');
    $curriculum = generationReadyDocument('SS2 Mathematics Curriculum');
    $private = generationReadyDocument('Pending Teacher Notes', ['approval_status' => KnowledgeDocument::APPROVAL_PENDING]);
    $lessonChunk = generationEmbeddedChunk($lesson, 'Factorisation rewrites a quadratic expression as a product of two linear factors.', 1.0, 0.0, 4);
    $curriculumChunk = generationEmbeddedChunk($curriculum, 'The roots are values of x that make the quadratic expression equal to zero.', 0.9, 0.1, 8);
    generationEmbeddedChunk($private, 'PRIVATE CONTENT MUST NOT LEAVE THE SERVER.', 1.0, 0.0, 1);
    generationConfirmCoverage($topic, $lessonChunk);
    generationConfirmCoverage($topic, $curriculumChunk);

    QuestionGeneratorAgent::fake([[
        'questions' => [
            generationMcq($lessonChunk->id),
            [
                'type' => 'short_answer',
                'difficulty' => 'medium',
                'content' => 'What is a root of a quadratic equation?',
                'explanation' => 'A root makes the expression zero.',
                'expected_answer' => 'A value of \\(x\\) for which the quadratic expression equals zero.',
                'options' => [],
                'marking_scheme' => [['point' => 'States that the expression equals zero.', 'weight' => 2]],
                'source_chunk_ids' => [$curriculumChunk->id],
            ],
        ],
    ]])->preventStrayPrompts();

    generationJob($run)->handle(app(QuestionGenerationContextService::class), app(QuestionGenerationService::class), app(GeneratedQuestionValidator::class));

    $run->refresh();
    expect($run)->status->toBe(QuestionGenerationRun::STATUS_COMPLETED)
        ->and($run->drafts)->toHaveCount(2)
        ->and($run->source_references)->toHaveCount(2)
        ->and(collect($run->source_references)->pluck('document_title')->all())
        ->toBe(['Quadratic Equations Lesson Note', 'SS2 Mathematics Curriculum'])
        ->and(Question::query()->count())->toBe(0);

    QuestionGeneratorAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Quadratic Equations Lesson Note')
        && str_contains($prompt->prompt, 'SS2 Mathematics Curriculum')
        && ! str_contains($prompt->prompt, 'PRIVATE CONTENT MUST NOT LEAVE THE SERVER.'));
    Http::assertSent(fn (Request $request): bool => str_contains((string) $request['input'], 'Quadratic Equations'));

    generationJob($run->refresh())->handle(app(QuestionGenerationContextService::class), app(QuestionGenerationService::class), app(GeneratedQuestionValidator::class));
    expect($run->fresh()->drafts)->toHaveCount(2);
});

test('insufficient approved knowledge fails clearly without prompting deepseek', function () {
    [$subject, $topic] = generationCurriculum();
    $run = generationRun(generationTeacher(), $subject, $topic);
    QuestionGeneratorAgent::fake()->preventStrayPrompts();
    Http::fake(['ollama.test:11434/api/embed' => Http::response(['embeddings' => [generationVector(1.0)]])]);

    expect(fn () => generationJob($run)->handle(app(QuestionGenerationContextService::class), app(QuestionGenerationService::class), app(GeneratedQuestionValidator::class)))
        ->toThrow(RuntimeException::class, 'not enough approved knowledge');

    expect($run->refresh())->status->toBe(QuestionGenerationRun::STATUS_FAILED)
        ->error->toContain('not enough approved knowledge');
    QuestionGeneratorAgent::assertNeverPrompted();
});

test('malformed provider output is stored as a partial review instead of silently accepted', function () {
    Http::fake(['ollama.test:11434/api/embed' => Http::response(['embeddings' => [generationVector(1.0)]])]);
    [$subject, $topic] = generationCurriculum();
    $run = generationRun(generationTeacher(), $subject, $topic);
    $document = generationReadyDocument('Algebra Note');
    $chunk = generationEmbeddedChunk($document, 'Quadratic equations have degree two.', 1.0);
    generationConfirmCoverage($topic, $chunk);
    $valid = generationMcq($chunk->id);
    $invalid = generationMcq('invented-source');
    $invalid['content'] = 'Which equation is linear?';
    $invalid['type'] = 'short_answer';
    $invalid['options'] = [];
    $invalid['expected_answer'] = null;
    QuestionGeneratorAgent::fake([['questions' => [$valid, $invalid]]]);

    generationJob($run)->handle(app(QuestionGenerationContextService::class), app(QuestionGenerationService::class), app(GeneratedQuestionValidator::class));

    expect($run->refresh())->status->toBe(QuestionGenerationRun::STATUS_PARTIAL)
        ->and($run->drafts()->where('is_valid', true)->count())->toBe(1)
        ->and($run->drafts()->where('is_valid', false)->first()->validation_errors)
        ->toContain('Written questions require an expected answer.')
        ->toContain('Written questions require a marking scheme.')
        ->toContain('One or more source citations were not supplied to the generator.');
});

test('provider failures are recorded and an authorized owner can queue a retry', function () {
    Queue::fake();
    Http::fake(['ollama.test:11434/api/embed' => Http::response(['embeddings' => [generationVector(1.0)]])]);
    [$subject, $topic] = generationCurriculum();
    $teacher = generationTeacher();
    $run = generationRun($teacher, $subject, $topic);
    $document = generationReadyDocument('Quadratic Equations Note');
    $chunk = generationEmbeddedChunk($document, 'A quadratic equation has degree two.', 1.0);
    generationConfirmCoverage($topic, $chunk);
    QuestionGeneratorAgent::fake(fn () => throw new RuntimeException('DeepSeek is temporarily unavailable.'));

    expect(fn () => generationJob($run)->handle(app(QuestionGenerationContextService::class), app(QuestionGenerationService::class), app(GeneratedQuestionValidator::class)))
        ->toThrow(RuntimeException::class, 'temporarily unavailable');
    expect($run->refresh())->status->toBe(QuestionGenerationRun::STATUS_FAILED)
        ->error->toContain('temporarily unavailable');

    $this->actingAs($teacher)->post(route('question-generations.retry', $run))->assertRedirect();
    expect($run->refresh())->status->toBe(QuestionGenerationRun::STATUS_PENDING)
        ->error->toBeNull();
    Queue::assertPushed(GenerateQuestionsFromKnowledgeJob::class, fn (GenerateQuestionsFromKnowledgeJob $job): bool => $job->runId === $run->id);
});

test('draft preview and editing are private to the requesting teacher', function () {
    [$subject, $topic] = generationCurriculum();
    $owner = generationTeacher();
    $otherTeacher = generationTeacher();
    $run = generationRun($owner, $subject, $topic, ['status' => QuestionGenerationRun::STATUS_COMPLETED]);
    $draft = GeneratedQuestionDraft::factory()->for($run, 'run')->create();

    $this->actingAs($otherTeacher)->get(route('question-generations.show', $run))->assertForbidden();
    $this->actingAs($otherTeacher)->patch(route('generated-question-drafts.update', $draft), generationDraftEdit())->assertForbidden();

    $this->actingAs($owner)->patch(route('generated-question-drafts.update', $draft), generationDraftEdit())->assertRedirect();

    expect($draft->refresh())->content->toContain('teacher-reviewed')
        ->is_valid->toBeFalse()
        ->edited_at->not->toBeNull()
        ->and(Question::query()->count())->toBe(0);
});

function generationTeacher(): User
{
    return User::factory()->create(['permissions' => [User::PERMISSION_GENERATE_QUESTIONS]]);
}

function generationCurriculum(): array
{
    $subject = Subject::factory()->create(['name' => 'Mathematics', 'level' => 'ss']);
    $topic = Topic::factory()->for($subject)->create(['name' => 'Quadratic Equations', 'class_level' => '10']);

    return [$subject, $topic];
}

function generationRequest(Subject $subject, Topic $topic): array
{
    return ['subject_id' => $subject->id, 'topic_id' => $topic->id, 'level' => 'ss', 'class_level' => '10', 'quantity' => 2, 'type_distribution' => ['multiple_choice' => 1, 'short_answer' => 1, 'theory' => 0], 'difficulty' => 'medium', 'additional_instructions' => 'Focus on factorisation.'];
}

function generationRun(User $user, Subject $subject, Topic $topic, array $attributes = []): QuestionGenerationRun
{
    return QuestionGenerationRun::factory()->create(['requested_by' => $user->id, 'subject_id' => $subject->id, 'topic_id' => $topic->id, 'level' => 'ss', 'class_level' => '10', 'requested_count' => 2, 'type_distribution' => ['multiple_choice' => 1, 'short_answer' => 1, 'theory' => 0], 'difficulty' => 'medium', ...$attributes]);
}

function generationReadyDocument(string $title, array $attributes = []): KnowledgeDocument
{
    return KnowledgeDocument::factory()->create(['title' => $title, 'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED, 'approval_status' => KnowledgeDocument::APPROVAL_APPROVED, 'embedding_status' => KnowledgeDocument::EMBEDDING_COMPLETED, 'embedding_provider' => 'ollama', 'embedding_model' => 'embeddinggemma-2', 'embedding_dimensions' => 768, 'approved_at' => now(), 'embedded_at' => now(), ...$attributes]);
}

function generationEmbeddedChunk(KnowledgeDocument $document, string $content, float $first, float $second = 0.0, int $page = 1): KnowledgeDocumentChunk
{
    $hash = hash('sha256', $content);

    return KnowledgeDocumentChunk::factory()->for($document, 'document')->create(['sequence' => 1, 'content' => $content, 'content_hash' => $hash, 'estimated_token_count' => 20, 'metadata' => ['page_start' => $page, 'page_end' => $page], 'embedding' => generationVector($first, $second), 'embedding_provider' => 'ollama', 'embedding_model' => 'embeddinggemma-2', 'embedding_dimensions' => 768, 'embedded_content_hash' => $hash, 'embedded_at' => now()]);
}

function generationMcq(string $sourceId): array
{
    return ['type' => 'multiple_choice', 'difficulty' => 'medium', 'content' => 'Which statement correctly describes factorisation?', 'explanation' => 'It rewrites an expression as a product.', 'expected_answer' => null, 'options' => [['content' => 'It writes an expression as a product of factors.', 'is_correct' => true], ['content' => 'It always differentiates the expression.', 'is_correct' => false], ['content' => 'It removes all variables.', 'is_correct' => false], ['content' => 'It changes every coefficient to zero.', 'is_correct' => false]], 'marking_scheme' => [], 'source_chunk_ids' => [$sourceId]];
}

function generationDraftEdit(): array
{
    return ['type' => 'multiple_choice', 'difficulty' => 'medium', 'content' => 'A teacher-reviewed question?', 'explanation' => 'Reviewed explanation.', 'expected_answer' => null, 'options' => [['content' => 'One', 'is_correct' => true], ['content' => 'Two', 'is_correct' => false], ['content' => 'Three', 'is_correct' => false], ['content' => 'Four', 'is_correct' => false]], 'marking_scheme' => []];
}

function generationJob(QuestionGenerationRun $run): GenerateQuestionsFromKnowledgeJob
{
    return new GenerateQuestionsFromKnowledgeJob($run->id);
}

function generationVector(float $first, float $second = 0.0): array
{
    $vector = array_fill(0, 768, 0.0);
    $vector[0] = $first;
    $vector[1] = $second;

    return $vector;
}

function generationConfirmCoverage(Topic $topic, KnowledgeDocumentChunk $chunk): void
{
    app(CurriculumMappingService::class)->review($chunk->document, null, [
        'action' => 'confirm', 'subject_id' => $topic->subject_id, 'class_level' => $topic->class_level,
        'topic_id' => $topic->id, 'evidence' => [['chunk_id' => $chunk->id]],
    ], User::factory()->create(['role' => User::ROLE_ADMIN]));
}
