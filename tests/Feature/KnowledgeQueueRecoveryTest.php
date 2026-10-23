<?php

use App\Http\Controllers\KnowledgeDocumentController;
use App\Jobs\EmbedKnowledgeDocumentJob;
use App\Jobs\GenerateQuestionsFromKnowledgeJob;
use App\Jobs\IdentifyCurriculumTopicsJob;
use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\GeneratedQuestionDraft;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\QuestionGenerationRun;
use App\Models\User;
use App\Services\GeneratedQuestionValidator;
use App\Services\KnowledgeDocumentService;
use App\Services\QuestionGenerationContextService;
use App\Services\QuestionGenerationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->actor = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->actingAs($this->actor);
});

test('approval survives broker failure and embedding can be queued again immediately', function () {
    $document = recoveryDocument();
    $bus = failingKnowledgeBroker();

    $this->from(route('knowledge-documents.show', $document))
        ->post(route('knowledge-documents.approve', $document))->assertRedirect()->assertSessionHas('error');
    expect($document->refresh()->approval_status)->toBe(KnowledgeDocument::APPROVAL_APPROVED)
        ->and($document->embedding_status)->toBe(KnowledgeDocument::EMBEDDING_FAILED)
        ->and($document->embedding_error)->not->toContain('secret')
        ->and($document->chunks()->count())->toBe(1);

    Bus::swap($bus);
    Queue::fake();
    $this->post(route('knowledge-documents.retry-embedding', $document))->assertRedirect();
    Queue::assertPushed(EmbedKnowledgeDocumentJob::class);
    expect($document->refresh()->embedding_status)->toBe(KnowledgeDocument::EMBEDDING_PENDING);
});

test('approval dispatches the version validated under the database lock', function () {
    Queue::fake();
    $stale = recoveryDocument();
    $stale->chunks()->update(['chunk_version' => 2]);
    KnowledgeDocument::query()->whereKey($stale->id)->update(['active_chunk_version' => 2]);
    $request = Request::create('/');
    $request->setUserResolver(fn () => $this->actor);

    app(KnowledgeDocumentController::class)->approve($request, $stale);
    Queue::assertPushed(EmbedKnowledgeDocumentJob::class, fn ($job) => $job->chunkVersion === 2);
});

test('failed processing retries remain retryable when the broker is unavailable', function () {
    $document = KnowledgeDocument::factory()->create(['processing_status' => KnowledgeDocument::PROCESSING_FAILED]);
    $bus = failingKnowledgeBroker();
    $this->post(route('knowledge-documents.retry', $document))->assertRedirect()->assertSessionHas('error');
    expect($document->refresh()->processing_status)->toBe(KnowledgeDocument::PROCESSING_FAILED);

    Bus::swap($bus);
    Queue::fake();
    $this->post(route('knowledge-documents.retry', $document))->assertRedirect();
    Queue::assertPushed(ProcessKnowledgeDocumentJob::class);
});

test('reprocessing broker failure retains the active index and allows immediate retry', function () {
    $document = recoveryDocument(['approval_status' => KnowledgeDocument::APPROVAL_APPROVED, 'embedding_status' => KnowledgeDocument::EMBEDDING_COMPLETED]);
    $chunkId = $document->chunks()->sole()->id;
    $bus = failingKnowledgeBroker();
    $this->post(route('knowledge-documents.reprocess', $document))->assertRedirect()->assertSessionHas('error');
    expect($document->refresh()->reprocessing_status)->toBe(KnowledgeDocument::REPROCESSING_FAILED)
        ->and($document->processing_status)->toBe(KnowledgeDocument::PROCESSING_COMPLETED)
        ->and($document->embedding_status)->toBe(KnowledgeDocument::EMBEDDING_COMPLETED)
        ->and($document->active_chunk_version)->toBe(1)
        ->and($document->chunks()->sole()->id)->toBe($chunkId);

    Bus::swap($bus);
    Queue::fake();
    $this->post(route('knowledge-documents.reprocess', $document))->assertRedirect();
    Queue::assertPushed(ProcessKnowledgeDocumentJob::class, fn ($job) => $job->reprocess);
});

test('upload broker failures retain private originals and expose no broker credentials', function () {
    Storage::fake('local');
    failingKnowledgeBroker();
    $file = UploadedFile::fake()->createWithContent('notes.pdf', '%PDF-1.7 lesson notes');
    $document = app(KnowledgeDocumentService::class)->create(['title' => 'Notes', 'document_type' => 'curriculum', 'description' => null], $file, $this->actor);
    $document->refresh();
    Storage::disk('local')->assertExists($document->path);
    expect($document->processing_status)->toBe(KnowledgeDocument::PROCESSING_FAILED)
        ->and($document->processing_error)->not->toContain('secret');
});

test('staged embedding and topic handoff failures do not destroy the active index', function () {
    $document = recoveryDocument(['approval_status' => KnowledgeDocument::APPROVAL_APPROVED, 'embedding_status' => KnowledgeDocument::EMBEDDING_COMPLETED, 'pending_chunk_version' => 2, 'reprocessing_status' => KnowledgeDocument::REPROCESSING_EMBEDDING]);
    failingKnowledgeBroker(2);
    expect(app(KnowledgeDocumentService::class)->queueEmbedding($document, 2))->toBeFalse()
        ->and(app(KnowledgeDocumentService::class)->queueTopicDetection($document))->toBeFalse();
    expect($document->refresh()->reprocessing_status)->toBe(KnowledgeDocument::REPROCESSING_FAILED)
        ->and($document->embedding_status)->toBe(KnowledgeDocument::EMBEDDING_COMPLETED)
        ->and($document->active_chunk_version)->toBe(1)
        ->and($document->topic_detection_status)->toBe('failed')
        ->and($document->chunks()->count())->toBe(1);
});

test('generation queue failures become failed runs with an immediately usable owner retry', function () {
    $run = QuestionGenerationRun::factory()->create(['requested_by' => $this->actor->id, 'status' => QuestionGenerationRun::STATUS_FAILED]);
    $bus = failingKnowledgeBroker();
    $this->post(route('question-generations.retry', $run))->assertRedirect()->assertSessionHas('error');
    $oldJobId = $run->refresh()->queue_job_id;
    expect($run->status)->toBe(QuestionGenerationRun::STATUS_FAILED)->and($run->error)->not->toContain('secret');

    Bus::swap($bus);
    Queue::fake();
    $this->post(route('question-generations.retry', $run))->assertRedirect();
    expect($run->refresh()->status)->toBe(QuestionGenerationRun::STATUS_PENDING)->and($run->queue_job_id)->not->toBe($oldJobId);
    Queue::assertPushed(GenerateQuestionsFromKnowledgeJob::class, fn ($job) => $job->queueJobId === $run->queue_job_id);
});

test('a deserialized timeout callback marks its own generation failed and retryable', function () {
    $run = QuestionGenerationRun::factory()->create();
    $job = new GenerateQuestionsFromKnowledgeJob($run->id);
    $payload = serialize($job);
    $run->update(['queue_job_id' => $job->queueJobId, 'generation_attempt' => 3, 'status' => QuestionGenerationRun::STATUS_GENERATING]);

    app(CallQueuedHandler::class)->failed(['command' => $payload], new TimeoutExceededException, $job->queueJobId);
    expect($run->refresh()->status)->toBe(QuestionGenerationRun::STATUS_FAILED)
        ->and($run->error)->toContain('time limit')
        ->and($run->completed_at)->not->toBeNull();
});

test('old timeouts and old queued attempts cannot overwrite a newer retry', function () {
    Queue::fake();
    $run = QuestionGenerationRun::factory()->create();
    $old = new GenerateQuestionsFromKnowledgeJob($run->id);
    $run->update(['queue_job_id' => $old->queueJobId, 'generation_attempt' => 2, 'status' => QuestionGenerationRun::STATUS_FAILED]);
    app(QuestionGenerationService::class)->queue($run);
    $currentId = $run->fresh()->queue_job_id;
    $old->failed(new TimeoutExceededException);
    $context = Mockery::mock(QuestionGenerationContextService::class);
    $context->shouldNotReceive('build');
    $old->handle($context, app(QuestionGenerationService::class), app(GeneratedQuestionValidator::class));
    expect($run->refresh()->queue_job_id)->toBe($currentId)
        ->and($run->status)->toBe(QuestionGenerationRun::STATUS_PENDING)
        ->and($run->generation_attempt)->toBe(2);
});

test('late generation failures preserve reviewed drafts', function () {
    $run = QuestionGenerationRun::factory()->create(['status' => QuestionGenerationRun::STATUS_COMPLETED]);
    $job = new GenerateQuestionsFromKnowledgeJob($run->id);
    $run->update(['queue_job_id' => $job->queueJobId]);
    GeneratedQuestionDraft::factory()->for($run, 'run')->create(['review_status' => 'approved']);
    unserialize(serialize($job))->failed(new TimeoutExceededException);
    expect($run->refresh()->status)->toBe(QuestionGenerationRun::STATUS_COMPLETED)
        ->and($run->drafts()->sole()->review_status)->toBe('approved');
});

test('a running generation attempt stops writing when a newer retry is queued', function () {
    Queue::fake();
    $run = QuestionGenerationRun::factory()->create();
    $job = new GenerateQuestionsFromKnowledgeJob($run->id);
    $context = Mockery::mock(QuestionGenerationContextService::class);
    $context->shouldReceive('build')->once()->andReturnUsing(function () use ($run): array {
        $run->update(['status' => QuestionGenerationRun::STATUS_FAILED]);
        app(QuestionGenerationService::class)->queue($run);

        return ['query' => 'quadratic equations', 'sources' => [], 'context' => ''];
    });
    $generator = Mockery::mock(QuestionGenerationService::class);
    $generator->shouldNotReceive('generate');
    $job->handle($context, $generator, app(GeneratedQuestionValidator::class));

    expect($run->refresh()->queue_job_id)->not->toBe($job->queueJobId)
        ->and($run->status)->toBe(QuestionGenerationRun::STATUS_PENDING)
        ->and($run->drafts()->count())->toBe(0);
});

test('topic detection broker failure can be retried through the authorized endpoint', function () {
    $document = recoveryDocument(['approval_status' => KnowledgeDocument::APPROVAL_APPROVED]);
    $bus = failingKnowledgeBroker();
    $this->post(route('curriculum.identify', $document))->assertRedirect()->assertSessionHas('error');
    expect($document->refresh()->topic_detection_status)->toBe('failed');

    Bus::swap($bus);
    Queue::fake();
    $this->post(route('curriculum.identify', $document))->assertRedirect();
    Queue::assertPushed(IdentifyCurriculumTopicsJob::class);
});

test('reprocessing CLI failures retain the active index and return failure', function () {
    $document = recoveryDocument(['approval_status' => KnowledgeDocument::APPROVAL_APPROVED, 'embedding_status' => KnowledgeDocument::EMBEDDING_COMPLETED]);
    failingKnowledgeBroker();
    $this->artisan('knowledge:reprocess', ['document' => $document->id])->assertFailed();
    expect($document->refresh()->reprocessing_status)->toBe(KnowledgeDocument::REPROCESSING_FAILED)
        ->and($document->active_chunk_version)->toBe(1)
        ->and($document->chunks()->count())->toBe(1);
});

test('embedding CLI reports broker failures instead of claiming documents were queued', function () {
    $document = recoveryDocument(['approval_status' => KnowledgeDocument::APPROVAL_APPROVED]);
    failingKnowledgeBroker();
    $this->artisan('knowledge:embed-approved')->assertFailed();
    expect($document->refresh()->embedding_status)->toBe(KnowledgeDocument::EMBEDDING_FAILED);
});

test('generation timeout covers both provider requests and rejects unsafe queue reservations', function () {
    config(['knowledge.embeddings.timeout' => 120, 'knowledge.generation.timeout' => 180]);
    $run = QuestionGenerationRun::factory()->create();
    $job = new GenerateQuestionsFromKnowledgeJob($run->id);
    expect($job->timeout)->toBe(360)->and($job->failOnTimeout)->toBeTrue();
    config(['queue.default' => 'redis', 'queue.connections.redis.retry_after' => 300]);
    Queue::fake();
    expect(app(QuestionGenerationService::class)->queue($run))->toBeFalse();
    expect($run->refresh()->status)->toBe(QuestionGenerationRun::STATUS_FAILED);
    Queue::assertNothingPushed();
});

function failingKnowledgeBroker(int $attempts = 1): mixed
{
    $bus = Bus::getFacadeRoot();
    Bus::partialMock()->shouldReceive('dispatch')->times($attempts)->andThrow(new RuntimeException('Queue broker unavailable: secret-password'));

    return $bus;
}

function recoveryDocument(array $attributes = []): KnowledgeDocument
{
    $document = KnowledgeDocument::factory()->create([
        'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
        ...$attributes,
    ]);
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create(['chunk_version' => 1]);

    return $document;
}
