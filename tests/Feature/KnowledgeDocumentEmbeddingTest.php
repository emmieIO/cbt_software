<?php

use App\Ai\Agents\CurriculumTopicAgent;
use App\Jobs\EmbedKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\User;
use App\Services\DocumentEmbeddingService;
use App\Services\KnowledgeRetrievalService;
use App\Services\OllamaEmbeddingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    CurriculumTopicAgent::fake([['topics' => []]]);
    config()->set('knowledge.embeddings.provider', 'ollama');
    config()->set('knowledge.embeddings.base_url', 'http://ollama.test:11434');
    config()->set('knowledge.embeddings.model', 'embeddinggemma-2');
    config()->set('knowledge.embeddings.dimensions', 768);
    config()->set('knowledge.embeddings.connect_timeout', 2);
    config()->set('knowledge.embeddings.timeout', 10);
    config()->set('knowledge.retrieval.candidate_count', 10);
    config()->set('knowledge.retrieval.similarity_threshold', 0.65);
});

test('document approval is permission protected and queues embeddings', function () {
    Queue::fake();
    $manager = User::factory()->create(['permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE]]);
    $approver = User::factory()->create(['permissions' => [User::PERMISSION_APPROVE_KNOWLEDGE_DOCUMENTS]]);
    $document = processedKnowledgeDocument();

    $this->actingAs($manager)->post(route('knowledge-documents.approve', $document))->assertForbidden();
    $this->actingAs($approver)
        ->post(route('knowledge-documents.approve', $document))
        ->assertRedirect(route('knowledge-documents.show', $document));

    expect($document->refresh())
        ->approval_status->toBe(KnowledgeDocument::APPROVAL_APPROVED)
        ->approved_by->toBe($approver->id)
        ->embedding_status->toBe(KnowledgeDocument::EMBEDDING_PENDING);
    Queue::assertPushed(EmbedKnowledgeDocumentJob::class, fn (EmbedKnowledgeDocumentJob $job): bool => $job->documentId === $document->id);
});

test('unprocessed documents cannot be approved', function () {
    Queue::fake();
    $approver = User::factory()->create(['permissions' => [User::PERMISSION_APPROVE_KNOWLEDGE_DOCUMENTS]]);
    $document = KnowledgeDocument::factory()->create();

    $this->actingAs($approver)->post(route('knowledge-documents.approve', $document))->assertStatus(409);
    Queue::assertNothingPushed();
});

test('unapproved content is never sent to the embedding provider', function () {
    Http::preventStrayRequests();
    $document = processedKnowledgeDocument();

    expect(fn () => app(DocumentEmbeddingService::class)->embed($document))
        ->toThrow(RuntimeException::class, 'Unapproved document content');

    Http::assertNothingSent();
});

test('ollama embedding service validates its request and response', function () {
    Http::fake([
        'ollama.test:11434/api/embed' => Http::response(['embeddings' => [knowledgeVector(1.0)]]),
    ]);

    $embedding = app(OllamaEmbeddingService::class)->embed('Quadratic equations');

    expect($embedding)->toHaveCount(768)->and($embedding[0])->toBe(1.0);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://ollama.test:11434/api/embed'
        && $request['model'] === 'embeddinggemma-2'
        && $request['input'] === 'Quadratic equations'
        && ! $request->hasHeader('Authorization'));
});

test('ollama embedding service reports api and connection failures clearly', function () {
    Http::fake([
        'ollama.test:11434/api/embed' => Http::response(['error' => 'model not found'], 500),
    ]);

    expect(fn () => app(OllamaEmbeddingService::class)->embed('Quadratic equations'))
        ->toThrow(RuntimeException::class, 'returned HTTP 500');

    Http::fake([
        'ollama.test:11434/api/embed' => Http::failedConnection('Connection refused'),
    ]);

    expect(fn () => app(OllamaEmbeddingService::class)->embed('Quadratic equations'))
        ->toThrow(RuntimeException::class, 'Could not connect');
});

test('embedding job embeds chunks and marks the document ready', function () {
    Http::fake([
        'ollama.test:11434/api/embed' => Http::response(['embeddings' => [knowledgeVector(1.0)]]),
    ]);
    $document = approvedKnowledgeDocument();
    KnowledgeDocumentChunk::factory()->count(3)->for($document, 'document')->sequence(
        fn ($sequence) => ['sequence' => $sequence->index + 1],
    )->create();

    (new EmbedKnowledgeDocumentJob($document->id))->handle(app(DocumentEmbeddingService::class));

    $document->refresh();
    $chunks = $document->chunks()->get();

    expect($document)
        ->embedding_status->toBe(KnowledgeDocument::EMBEDDING_COMPLETED)
        ->embedding_provider->toBe('ollama')
        ->embedding_model->toBe('embeddinggemma-2')
        ->embedding_dimensions->toBe(768)
        ->embedded_at->not->toBeNull()
        ->and($chunks)->toHaveCount(3)
        ->and($chunks->every(fn (KnowledgeDocumentChunk $chunk): bool => count($chunk->embedding ?? []) === 768))->toBeTrue()
        ->and($chunks->every(fn (KnowledgeDocumentChunk $chunk): bool => $chunk->embedded_content_hash === $chunk->content_hash))->toBeTrue();

    Http::assertSentCount(3);
});

test('embedding retries skip valid chunks and repair only missing work', function () {
    Http::fake([
        'ollama.test:11434/api/embed' => Http::response(['embeddings' => [knowledgeVector(1.0)]]),
    ]);
    $document = approvedKnowledgeDocument();
    $completeContent = 'A previously embedded quadratic equations chunk.';
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create([
        'sequence' => 1,
        'content' => $completeContent,
        'content_hash' => hash('sha256', $completeContent),
        'embedding' => knowledgeVector(1.0),
        'embedding_provider' => 'ollama',
        'embedding_model' => 'embeddinggemma-2',
        'embedding_dimensions' => 768,
        'embedded_content_hash' => hash('sha256', $completeContent),
        'embedded_at' => now(),
    ]);
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create([
        'sequence' => 2,
        'content' => 'This chunk still needs an embedding.',
    ]);

    (new EmbedKnowledgeDocumentJob($document->id))->handle(app(DocumentEmbeddingService::class));

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request['input'] === 'This chunk still needs an embedding.');
    expect($document->refresh()->embedding_status)->toBe(KnowledgeDocument::EMBEDDING_COMPLETED);
});

test('invalid provider output marks embedding as failed without declaring readiness', function () {
    Http::fake([
        'ollama.test:11434/api/embed' => Http::response(['embeddings' => [[1.0, 0.0]]]),
    ]);
    $document = approvedKnowledgeDocument();
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create();

    expect(fn () => (new EmbedKnowledgeDocumentJob($document->id))->handle(app(DocumentEmbeddingService::class)))
        ->toThrow(RuntimeException::class, 'exactly 768');

    expect($document->refresh())
        ->embedding_status->toBe(KnowledgeDocument::EMBEDDING_FAILED)
        ->embedding_error->toContain('exactly 768')
        ->embedded_at->toBeNull();
});

test('authorized approvers can retry failed embedding jobs', function () {
    Queue::fake();
    $approver = User::factory()->create(['permissions' => [User::PERMISSION_APPROVE_KNOWLEDGE_DOCUMENTS]]);
    $document = approvedKnowledgeDocument([
        'embedding_status' => KnowledgeDocument::EMBEDDING_FAILED,
        'embedding_error' => 'Provider unavailable.',
    ]);

    $this->actingAs($approver)
        ->post(route('knowledge-documents.retry-embedding', $document))
        ->assertRedirect(route('knowledge-documents.show', $document));

    expect($document->refresh())
        ->embedding_status->toBe(KnowledgeDocument::EMBEDDING_PENDING)
        ->embedding_error->toBeNull();
    Queue::assertPushed(EmbedKnowledgeDocumentJob::class, fn (EmbedKnowledgeDocumentJob $job): bool => $job->documentId === $document->id);
});

test('existing approved documents can be queued for indexing from the command', function () {
    Queue::fake();
    $document = approvedKnowledgeDocument();
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create();

    $this->artisan('knowledge:embed-approved')
        ->expectsOutput('Queued 1 approved knowledge document(s) for embedding.')
        ->assertSuccessful();

    Queue::assertPushed(EmbedKnowledgeDocumentJob::class, fn (EmbedKnowledgeDocumentJob $job): bool => $job->documentId === $document->id);
});

test('retrieval returns matching chunks across ready approved documents only', function () {
    Http::fake([
        'ollama.test:11434/api/embed' => Http::response(['embeddings' => [knowledgeVector(1.0)]]),
    ]);
    $first = readyKnowledgeDocument('SS2 Mathematics Lesson Note');
    $second = readyKnowledgeDocument('Algebra Curriculum');
    $unapproved = readyKnowledgeDocument('Unapproved Draft', ['approval_status' => KnowledgeDocument::APPROVAL_PENDING]);
    $wrongModel = readyKnowledgeDocument('Legacy Embeddings');
    $unembedded = readyKnowledgeDocument('Missing Embedding');

    embeddedChunk($first, 1, knowledgeVector(1.0), 'Quadratic equations can be solved by factorisation.');
    embeddedChunk($second, 1, knowledgeVector(0.8, 0.6), 'The roots are values that make the expression zero.');
    embeddedChunk($unapproved, 1, knowledgeVector(1.0), 'This content must not be retrieved.');
    embeddedChunk($wrongModel, 1, knowledgeVector(1.0), 'This uses a different model.', 'legacy-model');
    KnowledgeDocumentChunk::factory()->for($unembedded, 'document')->create(['sequence' => 1, 'embedding' => null]);

    $results = app(KnowledgeRetrievalService::class)->search('How are quadratic roots found?');

    expect($results)->toHaveCount(2)
        ->and($results->pluck('document.title')->all())->toBe(['SS2 Mathematics Lesson Note', 'Algebra Curriculum'])
        ->and((float) $results->first()->getAttribute('similarity'))->toBe(1.0);
});

test('semantic search interface requires knowledge base permission', function () {
    $unauthorized = User::factory()->create();
    $manager = User::factory()->create(['permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE]]);

    $this->actingAs($unauthorized)->get(route('knowledge-search.index'))->assertForbidden();
    $this->actingAs($manager)
        ->get(route('knowledge-search.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('KnowledgeBase/Search')
            ->where('results', []));
});

test('authorized users can inspect semantic search scores and sources', function () {
    Http::fake([
        'ollama.test:11434/api/embed' => Http::response(['embeddings' => [knowledgeVector(1.0)]]),
    ]);
    $manager = User::factory()->create(['permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE]]);
    $document = readyKnowledgeDocument('SS2 Mathematics - Quadratic Equations');
    embeddedChunk($document, 1, knowledgeVector(1.0), 'A quadratic equation has degree two.');

    $this->actingAs($manager)
        ->post(route('knowledge-search.search'), ['query' => 'What is a quadratic equation?'])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('KnowledgeBase/Search')
            ->where('query', 'What is a quadratic equation?')
            ->has('results', 1)
            ->where('results.0.document.title', 'SS2 Mathematics - Quadratic Equations')
            ->where('results.0.similarity', 1));
});

function processedKnowledgeDocument(array $attributes = []): KnowledgeDocument
{
    $document = KnowledgeDocument::factory()->create([
        'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
        ...$attributes,
    ]);
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create(['sequence' => 1]);

    return $document;
}

function approvedKnowledgeDocument(array $attributes = []): KnowledgeDocument
{
    return KnowledgeDocument::factory()->create([
        'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
        'approval_status' => KnowledgeDocument::APPROVAL_APPROVED,
        'approved_at' => now(),
        ...$attributes,
    ]);
}

function readyKnowledgeDocument(string $title, array $attributes = []): KnowledgeDocument
{
    return approvedKnowledgeDocument([
        'title' => $title,
        'embedding_status' => KnowledgeDocument::EMBEDDING_COMPLETED,
        'embedding_provider' => 'ollama',
        'embedding_model' => 'embeddinggemma-2',
        'embedding_dimensions' => 768,
        'embedded_at' => now(),
        ...$attributes,
    ]);
}

function embeddedChunk(
    KnowledgeDocument $document,
    int $sequence,
    array $embedding,
    string $content,
    string $model = 'embeddinggemma-2',
): KnowledgeDocumentChunk {
    $hash = hash('sha256', $content);

    return KnowledgeDocumentChunk::factory()->for($document, 'document')->create([
        'sequence' => $sequence,
        'content' => $content,
        'content_hash' => $hash,
        'embedding' => $embedding,
        'embedding_provider' => 'ollama',
        'embedding_model' => $model,
        'embedding_dimensions' => 768,
        'embedded_content_hash' => $hash,
        'embedded_at' => now(),
    ]);
}

/** @return array<int, float> */
function knowledgeVector(float $first, float $second = 0.0): array
{
    $vector = array_fill(0, 768, 0.0);
    $vector[0] = $first;
    $vector[1] = $second;

    return $vector;
}
