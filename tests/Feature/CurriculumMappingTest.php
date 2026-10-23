<?php

use App\Ai\Agents\CurriculumTopicAgent;
use App\Ai\Agents\QuestionGeneratorAgent;
use App\Jobs\EmbedKnowledgeDocumentJob;
use App\Jobs\GenerateQuestionsFromKnowledgeJob;
use App\Jobs\IdentifyCurriculumTopicsJob;
use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\Question;
use App\Models\QuestionGenerationRun;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\CurriculumMappingService;
use App\Services\CurriculumTopicIdentificationService;
use App\Services\DocumentEmbeddingService;
use App\Services\KnowledgeRetrievalService;
use App\Services\QuestionGenerationContextService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('knowledge.embeddings.base_url', 'http://ollama.test:11434');
    config()->set('knowledge.embeddings.model', 'embeddinggemma-2');
    config()->set('knowledge.embeddings.dimensions', 768);
    config()->set('knowledge.retrieval.similarity_threshold', 0.65);
    config()->set('knowledge.generation.minimum_similarity', 0.65);
    Http::preventStrayRequests();
    CurriculumTopicAgent::fake()->preventStrayPrompts();
    QuestionGeneratorAgent::fake()->preventStrayPrompts();
});

test('topic identification suggests existing and new topics without creating or confirming question bank topics', function () {
    [$subject, $topic] = cmCurriculum();
    $document = cmDocument();
    $chunk = cmChunk($document, 'SS2 Mathematics. Quadratic equations are solved by factorisation. Roots can also be found by completing the square.');
    CurriculumTopicAgent::fake([['topics' => [
        cmSuggestion($chunk, 'QUADRATIC-EQUATIONS'),
        cmSuggestion($chunk, 'Quadratic equation'),
        cmSuggestion($chunk, 'Completing the square'),
    ]]]);

    (new IdentifyCurriculumTopicsJob($document->id, 1))->handle(app(CurriculumTopicIdentificationService::class));
    $mappings = $document->curriculumMappings()->orderBy('detected_topic')->get();

    expect($mappings)->toHaveCount(2)
        ->and($mappings->where('topic_id', $topic->id)->count())->toBe(1)
        ->and($mappings->whereNull('topic_id')->count())->toBe(1)
        ->and($mappings->every(fn ($mapping) => $mapping->status === 'suggested'))->toBeTrue()
        ->and($document->refresh()->topic_detection_status)->toBe('completed')
        ->and(Topic::query()->count())->toBe(1)
        ->and(Question::query()->count())->toBe(0);
    QuestionGeneratorAgent::assertNeverPrompted();

    $job = new IdentifyCurriculumTopicsJob($document->id, 1);
    $job->handle(app(CurriculumTopicIdentificationService::class));
    expect($document->curriculumMappings()->count())->toBe(2);
});

test('topic detection leaves unsupported class and subject unknown for human review', function () {
    $document = cmDocument();
    $chunk = cmChunk($document, 'Quadratic equations have degree two. Factorisation can identify their roots.');
    $suggestion = cmSuggestion($chunk, 'Quadratic equations');
    $suggestion['subject_name'] = null;
    $suggestion['class_level'] = null;
    CurriculumTopicAgent::fake([['topics' => [$suggestion]]]);
    app(CurriculumTopicIdentificationService::class)->identify($document, 1);
    $mapping = $document->curriculumMappings()->sole();

    expect($mapping)->subject_id->toBeNull()->class_level->toBeNull()->topic_id->toBeNull()->status->toBe('suggested');
});

test('fabricated chunks or unsupported quotations roll back the batch and fail safely', function (bool $fabricatedId) {
    $document = cmDocument();
    $chunk = cmChunk($document, 'SS2 Mathematics. Quadratic equations have degree two.');
    $suggestion = cmSuggestion($chunk, 'Quadratic equations');
    if ($fabricatedId) {
        $suggestion['evidence'][0]['chunk_id'] = '01INVALID000000000000000000';
    } else {
        $suggestion['evidence'][0]['excerpt'] = 'This passage does not appear anywhere in the source.';
    }
    CurriculumTopicAgent::fake([['topics' => [$suggestion]]]);

    expect(fn () => (new IdentifyCurriculumTopicsJob($document->id, 1))->handle(app(CurriculumTopicIdentificationService::class)))->toThrow(Exception::class);
    expect($document->refresh()->topic_detection_status)->toBe('failed')
        ->and($document->curriculumMappings()->count())->toBe(0)
        ->and($document->topic_detection_cursor)->toBe(0);
})->with([true, false]);

test('unapproved documents are not sent to topic identification', function () {
    $document = cmDocument(['approval_status' => 'pending']);
    cmChunk($document, 'Private unapproved lesson note.');
    (new IdentifyCurriculumTopicsJob($document->id, 1))->handle(app(CurriculumTopicIdentificationService::class));
    CurriculumTopicAgent::assertNeverPrompted();
});

test('mapping confirmation rejection manual addition and topic creation respect permissions', function () {
    [$subject, $topic] = cmCurriculum();
    $document = cmDocument();
    $chunk = cmChunk($document, 'SS2 Mathematics. Quadratic equations are solved through factorisation.');
    $denied = User::factory()->create(['permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE]]);
    $reviewer = cmReviewer();
    $payload = cmPayload($topic, $chunk);

    $this->actingAs($denied)->post(route('curriculum.store', $document), $payload)->assertForbidden();
    $this->actingAs($denied)->post(route('curriculum.identify', $document))->assertForbidden();
    $this->actingAs($reviewer)->post(route('curriculum.store', $document), $payload)->assertRedirect();
    $mapping = $document->curriculumMappings()->sole();
    expect($mapping)->status->toBe('confirmed')->provenance->toBe('manual')->reviewed_by->toBe($reviewer->id)->reviewed_at->not->toBeNull();
    $this->actingAs($reviewer)->patch(route('curriculum.update', [$document, $mapping]), ['action' => 'reject'])->assertRedirect();
    expect($mapping->refresh()->status)->toBe('rejected')
        ->and(app(CurriculumMappingService::class)->chunkIds($subject->id, '11', $topic->id))->toBe([]);
});

test('new topic creation requires explicit permission and reuses capitalization punctuation and singular variants', function () {
    [$subject, $topic] = cmCurriculum();
    $document = cmDocument();
    $chunk = cmChunk($document, 'SS2 Mathematics. The quadratic equation can be solved by factorisation.');
    $payload = [...cmPayload($topic, $chunk), 'topic_id' => null, 'new_topic_name' => 'QUADRATIC equation'];
    $this->actingAs(cmReviewer())->post(route('curriculum.store', $document), $payload)->assertSessionHasErrors('topic_id');
    $reviewer = cmReviewer([User::PERMISSION_CREATE_QUESTIONS]);
    $this->actingAs($reviewer)->post(route('curriculum.store', $document), $payload)->assertRedirect();
    expect(Topic::query()->count())->toBe(1)
        ->and($document->curriculumMappings()->sole()->topic_id)->toBe($topic->id);
    $otherDocument = cmDocument();
    $otherChunk = cmChunk($otherDocument, 'Completing the square is another quadratic solution method.');
    $this->actingAs($reviewer)->post(route('curriculum.store', $otherDocument), [...cmPayload($topic, $otherChunk), 'topic_id' => null, 'new_topic_name' => 'Completing the square'])->assertRedirect();
    expect(Topic::query()->count())->toBe(2);
});

test('duplicate mapping submissions and cross-document evidence are rejected', function () {
    [, $topic] = cmCurriculum();
    $document = cmDocument();
    $chunk = cmChunk($document, 'Quadratic equations are solved by factorisation.');
    $otherDocument = cmDocument();
    $otherChunk = cmChunk($otherDocument, 'This source is in another document.');
    $this->actingAs(cmReviewer())->post(route('curriculum.store', $document), cmPayload($topic, $otherChunk))->assertSessionHasErrors('evidence');
    $this->post(route('curriculum.store', $document), cmPayload($topic, $chunk))->assertRedirect();
    $this->post(route('curriculum.store', $document), cmPayload($topic, $chunk))->assertSessionHasErrors('topic_id');
    expect($document->curriculumMappings()->count())->toBe(1);
});

test('existing topic matching remains scoped to the correct subject and class', function () {
    [$subject, $topic] = cmCurriculum();
    $wrongClass = Topic::factory()->for($subject)->create(['name' => $topic->name, 'class_level' => '10']);
    $otherSubject = Subject::factory()->create(['name' => 'Physics', 'level' => 'ss']);
    $wrongSubject = Topic::factory()->for($otherSubject)->create(['name' => $topic->name, 'class_level' => '11']);
    $service = app(CurriculumMappingService::class);
    expect($service->matchTopic('quadratic-equations', $subject->id, '11')->id)->toBe($topic->id)
        ->and($service->matchTopic('quadratic-equations', $subject->id, '10')->id)->toBe($wrongClass->id)
        ->and($service->matchTopic('quadratic-equations', $otherSubject->id, '11')->id)->toBe($wrongSubject->id);
});

test('curriculum retrieval uses mapped chunks across documents and never falls back to all similar content', function () {
    [$subject, $topic] = cmCurriculum();
    $first = cmDocument();
    $second = cmDocument();
    $unmapped = cmDocument();
    $firstChunk = cmChunk($first, 'Quadratic equations are solved by factorisation.');
    $secondChunk = cmChunk($second, 'Quadratic roots satisfy the equation when substituted.');
    cmChunk($unmapped, 'Unmapped content with a perfect semantic similarity.');
    cmChunk($first, 'This unrelated section in the same document must be excluded.', 2);
    cmConfirm($topic, $firstChunk);
    cmConfirm($topic, $secondChunk);
    Http::fake(['ollama.test:11434/api/embed' => Http::response(['embeddings' => [cmVector()]])]);
    $run = QuestionGenerationRun::factory()->create(['subject_id' => $subject->id, 'topic_id' => $topic->id, 'class_level' => '11', 'level' => 'ss']);
    $context = app(QuestionGenerationContextService::class)->build($run);
    expect(array_column($context['sources'], 'chunk_id'))->toEqualCanonicalizing([$firstChunk->id, $secondChunk->id]);
    expect($context['context'])->not->toContain('Unmapped content')->not->toContain('unrelated section');
    $first->curriculumMappings()->update(['status' => 'suggested']);
    $second->curriculumMappings()->update(['status' => 'rejected']);
    expect(fn () => app(QuestionGenerationContextService::class)->build($run))->toThrow(RuntimeException::class, 'confirmed curriculum coverage');
    expect(app(KnowledgeRetrievalService::class)->search('quadratic', chunkIds: []))->toHaveCount(0);
    Http::assertSentCount(1);
});

test('covered topics and source previews exclude ineligible mappings and preserve diagnostic search', function () {
    [$subject, $topic] = cmCurriculum();
    $document = cmDocument();
    $chunk = cmChunk($document, 'Quadratic equations can be solved by factorisation.');
    $mapping = cmConfirm($topic, $chunk);
    $teacher = User::factory()->create(['permissions' => [User::PERMISSION_GENERATE_QUESTIONS]]);
    $this->actingAs($teacher)->get(route('question-generations.index'))->assertInertia(fn (Assert $page) => $page->has('subjects', 1)->has('subjects.0.topics', 1));
    $this->getJson(route('question-generations.sources', ['topic_id' => $topic->id]))->assertOk()->assertJsonPath('sources.0.document_id', $document->id)->assertJsonPath('sources.0.evidence.0.chunk_id', $chunk->id);
    foreach ([['approval_status' => 'pending'], ['approval_status' => 'rejected'], ['approval_status' => 'approved', 'embedding_status' => 'failed']] as $attributes) {
        $document->update($attributes);
        expect(app(CurriculumMappingService::class)->chunkIds($subject->id, '11', $topic->id))->toBe([]);
        $this->getJson(route('question-generations.sources', ['topic_id' => $topic->id]))->assertJsonPath('sources', []);
    }
    $document->update(['approval_status' => 'approved', 'embedding_status' => 'completed']);
    $mapping->update(['status' => 'suggested']);
    $this->get(route('question-generations.index'))->assertInertia(fn (Assert $page) => $page->has('subjects', 0));
    Http::fake(['ollama.test:11434/api/embed' => Http::response(['embeddings' => [cmVector()]])]);
    expect(app(KnowledgeRetrievalService::class)->search('quadratic'))->toHaveCount(1);
});

test('safe chunk replacement preserves confirmation only when all quoted evidence can be re-established', function (bool $preserve) {
    Queue::fake();
    [$subject, $topic] = cmCurriculum();
    $document = cmDocument(['pending_chunk_version' => 2]);
    $text = 'Quadratic equations are solved by factorisation.';
    $oldChunk = cmChunk($document, $text);
    $mapping = cmConfirm($topic, $oldChunk);
    $replacement = cmChunk($document, $preserve ? 'New heading. '.$text.' Further explanation.' : 'This replacement covers only trigonometry.', 1, 2);
    (new EmbedKnowledgeDocumentJob($document->id, 2))->handle(app(DocumentEmbeddingService::class));
    expect($document->refresh()->active_chunk_version)->toBe(2)
        ->and($mapping->refresh()->status)->toBe('confirmed')
        ->and($mapping->reviewed_by)->not->toBeNull();
    if ($preserve) {
        expect($mapping->chunk_version)->toBe(2)->and($mapping->evidence_status)->toBe('current')
            ->and(app(CurriculumMappingService::class)->chunkIds($subject->id, '11', $topic->id))->toBe([$replacement->id]);
    } else {
        expect($mapping->evidence_status)->toBe('needs_review')
            ->and($mapping->evidence()->sole()->knowledge_document_chunk_id)->toBeNull()
            ->and($mapping->evidence()->sole()->excerpt)->toBe($text)
            ->and(app(CurriculumMappingService::class)->chunkIds($subject->id, '11', $topic->id))->toBe([]);
    }
})->with([true, false]);

test('failed staged embeddings leave existing confirmed coverage available', function () {
    [$subject, $topic] = cmCurriculum();
    $document = cmDocument(['pending_chunk_version' => 2]);
    $chunk = cmChunk($document, 'Quadratic equations have degree two.');
    cmConfirm($topic, $chunk);
    cmChunk($document, 'A new explanation awaiting embeddings.', 1, 2, false);
    Http::fake(['ollama.test:11434/api/embed' => Http::response(['error' => 'Unavailable'], 503)]);
    expect(fn () => (new EmbedKnowledgeDocumentJob($document->id, 2))->handle(app(DocumentEmbeddingService::class)))->toThrow(RuntimeException::class);
    expect(app(CurriculumMappingService::class)->chunkIds($subject->id, '11', $topic->id))->toBe([$chunk->id]);
});

test('deleted or changed evidence and soft deleted documents are excluded immediately', function () {
    [$subject, $topic] = cmCurriculum();
    $document = cmDocument();
    $chunk = cmChunk($document, 'Quadratic equations have degree two.');
    cmConfirm($topic, $chunk);
    $chunk->update(['content_hash' => hash('sha256', 'changed')]);
    expect(app(CurriculumMappingService::class)->chunkIds($subject->id, '11', $topic->id))->toBe([]);
    $document->delete();
    expect(app(CurriculumMappingService::class)->coverage())->toHaveCount(0);
});

test('detection checkpoints bound provider calls and safely continue after a failure', function () {
    config()->set('knowledge.curriculum.requests_per_job', 1);
    config()->set('knowledge.curriculum.batch_characters', 2000);
    $document = cmDocument();
    cmChunk($document, str_repeat('Factorisation explanation. ', 60), 1);
    cmChunk($document, str_repeat('Roots and quadratic explanations. ', 55), 2);
    CurriculumTopicAgent::fake([['topics' => []]]);
    app(CurriculumTopicIdentificationService::class)->identify($document, 1);
    expect($document->refresh())->topic_detection_status->toBe('partial')->topic_detection_cursor->toBe(1);
    CurriculumTopicAgent::fake(fn () => throw new RuntimeException('Provider unavailable'));
    expect(fn () => (new IdentifyCurriculumTopicsJob($document->id, 1))->handle(app(CurriculumTopicIdentificationService::class)))->toThrow(RuntimeException::class);
    expect($document->refresh()->topic_detection_cursor)->toBe(1);
    CurriculumTopicAgent::fake([['topics' => []]]);
    (new IdentifyCurriculumTopicsJob($document->id, 1))->handle(app(CurriculumTopicIdentificationService::class));
    expect($document->refresh())->topic_detection_status->toBe('completed')->topic_detection_cursor->toBe(2);
});

test('an absent class never resolves an ambiguous subject across school levels', function () {
    Subject::factory()->create(['name' => 'Mathematics', 'level' => 'js']);
    Subject::factory()->create(['name' => 'Mathematics', 'level' => 'ss']);
    $document = cmDocument();
    $chunk = cmChunk($document, 'Mathematics. Quadratic equations have degree two.');
    $suggestion = cmSuggestion($chunk, 'Quadratic equations');
    $suggestion['class_level'] = null;
    CurriculumTopicAgent::fake([['topics' => [$suggestion]]]);
    app(CurriculumTopicIdentificationService::class)->identify($document, 1);
    expect($document->curriculumMappings()->sole())->subject_id->toBeNull()->class_level->toBeNull();
});

test('generation submissions cannot select a bank topic without confirmed coverage', function () {
    Queue::fake();
    [$subject, $topic] = cmCurriculum();
    $teacher = User::factory()->create(['permissions' => [User::PERMISSION_GENERATE_QUESTIONS]]);
    $this->actingAs($teacher)->post(route('question-generations.store'), [
        'subject_id' => $subject->id, 'topic_id' => $topic->id, 'class_level' => '11', 'level' => 'ss',
        'quantity' => 1, 'type_distribution' => ['multiple_choice' => 1, 'short_answer' => 0, 'theory' => 0], 'difficulty' => 'medium',
    ])->assertSessionHasErrors('topic_id');
    expect(QuestionGenerationRun::query()->count())->toBe(0);
    Queue::assertNotPushed(GenerateQuestionsFromKnowledgeJob::class);
});

test('identification queueing and nested review are protected against duplicate and cross document requests', function () {
    Queue::fake();
    [, $topic] = cmCurriculum();
    $document = cmDocument();
    $chunk = cmChunk($document, 'Quadratic equations have degree two.');
    $mapping = cmConfirm($topic, $chunk);
    $otherDocument = cmDocument();
    $this->actingAs(cmReviewer())->patch(route('curriculum.update', [$otherDocument, $mapping]), ['action' => 'reject'])->assertNotFound();
    $this->post(route('curriculum.identify', $document))->assertRedirect();
    $this->post(route('curriculum.identify', $document))->assertStatus(409);
    Queue::assertPushed(IdentifyCurriculumTopicsJob::class, 1);
});

test('merged replacement chunks retain every previously reviewed quotation', function () {
    Queue::fake();
    [, $topic] = cmCurriculum();
    $document = cmDocument(['pending_chunk_version' => 2]);
    $first = cmChunk($document, 'Factorisation produces a product of linear factors.');
    $second = cmChunk($document, 'Roots are the values that make the expression equal zero.', 2);
    $mapping = app(CurriculumMappingService::class)->review($document, null, [
        ...cmPayload($topic, $first), 'evidence' => [['chunk_id' => $first->id], ['chunk_id' => $second->id]],
    ], cmReviewer());
    $replacement = cmChunk($document, $first->content.' '.$second->content, 1, 2);
    (new EmbedKnowledgeDocumentJob($document->id, 2))->handle(app(DocumentEmbeddingService::class));
    expect($mapping->refresh()->evidence()->count())->toBe(2)
        ->and($mapping->evidence()->pluck('knowledge_document_chunk_id')->unique()->all())->toBe([$replacement->id]);
    $document->refresh()->update(['active_chunk_version' => 3]);
    cmChunk($document, $second->content, 1, 3);
    app(CurriculumMappingService::class)->revalidate($document);
    expect($mapping->refresh()->evidence_status)->toBe('needs_review');
});

test('mapping rejection during query embedding immediately removes the source from generation', function () {
    [$subject, $topic] = cmCurriculum();
    $document = cmDocument();
    $chunk = cmChunk($document, 'Quadratic equations have degree two.');
    $mapping = cmConfirm($topic, $chunk);
    Http::fake(function () use ($mapping) {
        $mapping->update(['status' => 'rejected']);

        return Http::response(['embeddings' => [cmVector()]]);
    });
    $run = QuestionGenerationRun::factory()->create(['subject_id' => $subject->id, 'topic_id' => $topic->id, 'class_level' => '11', 'level' => 'ss']);
    expect(fn () => app(QuestionGenerationContextService::class)->build($run))->toThrow(RuntimeException::class, 'not enough approved knowledge');
});

test('obsolete embedding deliveries and failure callbacks cannot disable a newer active index', function () {
    $document = cmDocument(['active_chunk_version' => 2]);
    $job = new EmbedKnowledgeDocumentJob($document->id, 1);
    $job->handle(app(DocumentEmbeddingService::class));
    $job->failed(new RuntimeException('An old delivery failed.'));
    expect($document->refresh())->embedding_status->toBe('completed')->active_chunk_version->toBe(2);
    Http::assertNothingSent();
});

test('topic identification cannot write an old response after active version changes', function () {
    $document = cmDocument();
    cmChunk($document, 'Quadratic equations have degree two.');
    CurriculumTopicAgent::fake(function () use ($document) {
        $document->refresh()->update(['active_chunk_version' => 2, 'topic_detection_status' => 'pending', 'topic_detection_version' => null, 'topic_detection_cursor' => 0]);

        return ['topics' => []];
    });
    expect(fn () => (new IdentifyCurriculumTopicsJob($document->id, 1))->handle(app(CurriculumTopicIdentificationService::class)))->toThrow(RuntimeException::class);
    expect($document->refresh())->topic_detection_status->toBe('pending')->topic_detection_cursor->toBe(0)
        ->and($document->curriculumMappings()->count())->toBe(0);
});

function cmReviewer(array $permissions = []): User
{
    return User::factory()->create(['permissions' => [User::PERMISSION_REVIEW_CURRICULUM, ...$permissions]]);
}
function cmCurriculum(): array
{
    $subject = Subject::factory()->create(['name' => 'Mathematics', 'level' => 'ss']);
    $topic = Topic::factory()->for($subject)->create(['name' => 'Quadratic Equations', 'class_level' => '11']);

    return [$subject, $topic];
}
function cmDocument(array $attributes = []): KnowledgeDocument
{
    return KnowledgeDocument::factory()->create(['processing_status' => 'completed', 'approval_status' => 'approved', 'embedding_status' => 'completed', 'embedding_provider' => 'ollama', 'embedding_model' => 'embeddinggemma-2', 'embedding_dimensions' => 768, ...$attributes]);
}
function cmVector(): array
{
    return [1.0, ...array_fill(0, 767, 0.0)];
}
function cmChunk(KnowledgeDocument $document, string $text, int $sequence = 1, int $version = 1, bool $embedded = true): KnowledgeDocumentChunk
{
    return KnowledgeDocumentChunk::factory()->for($document, 'document')->create(['sequence' => $sequence, 'chunk_version' => $version, 'content' => $text, 'content_hash' => hash('sha256', $text), 'embedding' => $embedded ? cmVector() : null, 'embedding_provider' => $embedded ? 'ollama' : null, 'embedding_model' => $embedded ? 'embeddinggemma-2' : null, 'embedding_dimensions' => $embedded ? 768 : null, 'embedded_content_hash' => $embedded ? hash('sha256', $text) : null, 'metadata' => ['page_start' => 3, 'page_end' => 3]]);
}
function cmPayload(Topic $topic, KnowledgeDocumentChunk $chunk): array
{
    return ['action' => 'confirm', 'subject_id' => $topic->subject_id, 'class_level' => $topic->class_level, 'topic_id' => $topic->id, 'evidence' => [['chunk_id' => $chunk->id]]];
}
function cmConfirm(Topic $topic, KnowledgeDocumentChunk $chunk): CurriculumTopicMapping
{
    return app(CurriculumMappingService::class)->review($chunk->document, null, cmPayload($topic, $chunk), cmReviewer());
}
function cmSuggestion(KnowledgeDocumentChunk $chunk, string $name): array
{
    return ['topic_name' => $name, 'subject_name' => 'Mathematics', 'class_level' => '11', 'section_heading' => $name, 'evidence' => [['chunk_id' => $chunk->id, 'excerpt' => mb_substr($chunk->content, 0, 500)]]];
}
