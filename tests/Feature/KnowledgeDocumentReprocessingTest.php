<?php

use App\Ai\Agents\CurriculumTopicAgent;
use App\Jobs\EmbedKnowledgeDocumentJob;
use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\User;
use App\Services\DocumentEmbeddingService;
use App\Services\DocumentTextExtractionService;
use App\Services\ExtractedDocumentText;
use App\Services\KnowledgeRetrievalService;
use App\Services\TextChunkingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\mock;

beforeEach(function () {
    CurriculumTopicAgent::fake([['topics' => []]]);
    config()->set('knowledge.embeddings.provider', 'ollama');
    config()->set('knowledge.embeddings.base_url', 'http://ollama.test:11434');
    config()->set('knowledge.embeddings.model', 'embeddinggemma-2');
    config()->set('knowledge.embeddings.dimensions', 768);
    config()->set('knowledge.retrieval.candidate_count', 10);
    config()->set('knowledge.retrieval.similarity_threshold', 0.65);
});

test('safe reprocessing keeps the active index searchable until the replacement is embedded', function () {
    Storage::fake('local');
    Queue::fake();
    Http::fake([
        'ollama.test:11434/api/embed' => Http::response(['embeddings' => [reprocessingVector(1.0)]]),
    ]);
    $document = reprocessingReadyDocument();
    $oldChunk = reprocessingEmbeddedChunk($document, 1, 1, 'The old approved explanation of factorisation.');
    $extractor = mock(DocumentTextExtractionService::class);
    $extractor->shouldReceive('extract')->once()->andReturn(replacementLessonText());

    (new ProcessKnowledgeDocumentJob($document->id, true))->handle($extractor, new TextChunkingService);

    $document->refresh();
    $stagedVersion = $document->pending_chunk_version;

    expect($document->active_chunk_version)->toBe(1)
        ->and($stagedVersion)->toBe(2)
        ->and($document->reprocessing_status)->toBe(KnowledgeDocument::REPROCESSING_EMBEDDING)
        ->and($document->chunks()->where('chunk_version', 1)->value('id'))->toBe($oldChunk->id)
        ->and($document->chunks()->where('chunk_version', 2)->exists())->toBeTrue();

    $searchResults = app(KnowledgeRetrievalService::class)->search('factorisation');
    expect($searchResults)->toHaveCount(1)
        ->and($searchResults->first()->id)->toBe($oldChunk->id);

    $queuedJob = null;
    Queue::assertPushed(EmbedKnowledgeDocumentJob::class, function (EmbedKnowledgeDocumentJob $job) use (&$queuedJob, $stagedVersion): bool {
        $queuedJob = $job;

        return $job->chunkVersion === $stagedVersion;
    });
    expect($queuedJob)->not->toBeNull();

    $queuedJob->handle(app(DocumentEmbeddingService::class));
    $document->refresh();

    expect($document->active_chunk_version)->toBe(2)
        ->and($document->pending_chunk_version)->toBeNull()
        ->and($document->reprocessing_status)->toBeNull()
        ->and($document->chunks()->where('chunk_version', 1)->exists())->toBeFalse()
        ->and($document->chunks()->where('chunk_version', 2)->whereNotNull('embedding')->exists())->toBeTrue();
});

test('failed replacement embedding leaves the previous working index available', function () {
    Storage::fake('local');
    Queue::fake();
    $document = reprocessingReadyDocument();
    $oldChunk = reprocessingEmbeddedChunk($document, 1, 1, 'Stable approved quadratic content.');
    $extractor = mock(DocumentTextExtractionService::class);
    $extractor->shouldReceive('extract')->once()->andReturn(replacementLessonText());

    (new ProcessKnowledgeDocumentJob($document->id, true))->handle($extractor, new TextChunkingService);
    $stagedVersion = $document->refresh()->pending_chunk_version;
    Http::fake(fn (Request $request) => Http::response([
        'embeddings' => [
            $request['input'] === 'quadratic'
                ? reprocessingVector(1.0)
                : [1.0, 0.0],
        ],
    ]));

    expect(fn () => (new EmbedKnowledgeDocumentJob($document->id, $stagedVersion))
        ->handle(app(DocumentEmbeddingService::class)))
        ->toThrow(RuntimeException::class, 'exactly 768');

    $document->refresh();
    expect($document->active_chunk_version)->toBe(1)
        ->and($document->embedding_status)->toBe(KnowledgeDocument::EMBEDDING_COMPLETED)
        ->and($document->reprocessing_status)->toBe(KnowledgeDocument::REPROCESSING_FAILED)
        ->and($document->reprocessing_error)->toContain('exactly 768')
        ->and($document->chunks()->whereKey($oldChunk->id)->exists())->toBeTrue();

    $searchResults = app(KnowledgeRetrievalService::class)->search('quadratic');
    expect($searchResults)->toHaveCount(1)
        ->and($searchResults->first()->id)->toBe($oldChunk->id);
});

test('reprocessing is authorized and repeated requests do not queue duplicate work', function () {
    Queue::fake();
    $unauthorized = User::factory()->create();
    $manager = User::factory()->create(['permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE]]);
    $document = reprocessingReadyDocument();
    reprocessingEmbeddedChunk($document, 1, 1, 'Active text.');

    $this->actingAs($unauthorized)
        ->post(route('knowledge-documents.reprocess', $document))
        ->assertForbidden();

    $this->actingAs($manager)
        ->post(route('knowledge-documents.reprocess', $document))
        ->assertRedirect(route('knowledge-documents.show', $document));

    $this->actingAs($manager)
        ->post(route('knowledge-documents.reprocess', $document))
        ->assertStatus(409);

    Queue::assertPushed(ProcessKnowledgeDocumentJob::class, 1);
    Queue::assertPushed(ProcessKnowledgeDocumentJob::class, fn (ProcessKnowledgeDocumentJob $job): bool => $job->reprocess);
});

test('reprocessing an unapproved document activates extracted chunks without sending embeddings', function () {
    Storage::fake('local');
    Queue::fake();
    $content = 'unapproved private PDF content';
    $path = 'knowledge-documents/testing/unapproved-reprocessing.pdf';
    Storage::disk('local')->put($path, $content);
    $document = KnowledgeDocument::factory()->create([
        'path' => $path,
        'size_bytes' => strlen($content),
        'checksum' => hash('sha256', $content),
        'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
        'approval_status' => KnowledgeDocument::APPROVAL_PENDING,
        'active_chunk_version' => 1,
    ]);
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create([
        'chunk_version' => 1,
        'sequence' => 1,
        'content' => 'Previous extracted text.',
    ]);
    $extractor = mock(DocumentTextExtractionService::class);
    $extractor->shouldReceive('extract')->once()->andReturn(replacementLessonText());

    (new ProcessKnowledgeDocumentJob($document->id, true))->handle($extractor, new TextChunkingService);

    expect($document->refresh())
        ->active_chunk_version->toBe(2)
        ->pending_chunk_version->toBeNull()
        ->reprocessing_status->toBeNull()
        ->embedding_status->toBe(KnowledgeDocument::EMBEDDING_PENDING)
        ->and($document->chunks()->pluck('chunk_version')->all())->toBe([2]);
    Queue::assertNotPushed(EmbedKnowledgeDocumentJob::class);
});

test('the command queues one document for safe reprocessing', function () {
    Queue::fake();
    $document = reprocessingReadyDocument();

    $this->artisan('knowledge:reprocess', ['document' => $document->id])
        ->expectsOutput("Queued knowledge document {$document->id} for safe reprocessing.")
        ->assertSuccessful();

    Queue::assertPushed(ProcessKnowledgeDocumentJob::class, fn (ProcessKnowledgeDocumentJob $job): bool => $job->reprocess);
});

function reprocessingReadyDocument(): KnowledgeDocument
{
    $content = 'original private PDF content';
    $path = 'knowledge-documents/testing/reprocessing.pdf';
    Storage::disk('local')->put($path, $content);

    return KnowledgeDocument::factory()->create([
        'path' => $path,
        'size_bytes' => strlen($content),
        'checksum' => hash('sha256', $content),
        'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
        'approval_status' => KnowledgeDocument::APPROVAL_APPROVED,
        'approved_at' => now(),
        'embedding_status' => KnowledgeDocument::EMBEDDING_COMPLETED,
        'embedding_provider' => 'ollama',
        'embedding_model' => 'embeddinggemma-2',
        'embedding_dimensions' => 768,
        'embedded_at' => now(),
        'active_chunk_version' => 1,
    ]);
}

function reprocessingEmbeddedChunk(
    KnowledgeDocument $document,
    int $version,
    int $sequence,
    string $content,
): KnowledgeDocumentChunk {
    $hash = hash('sha256', $content);

    return KnowledgeDocumentChunk::factory()->for($document, 'document')->create([
        'chunk_version' => $version,
        'sequence' => $sequence,
        'content' => $content,
        'content_hash' => $hash,
        'embedding' => reprocessingVector(1.0),
        'embedding_provider' => 'ollama',
        'embedding_model' => 'embeddinggemma-2',
        'embedding_dimensions' => 768,
        'embedded_content_hash' => $hash,
        'embedded_at' => now(),
    ]);
}

function replacementLessonText(): ExtractedDocumentText
{
    return new ExtractedDocumentText(
        text: 'FACTORISATION',
        pages: [[
            'number' => 4,
            'text' => "FACTORISATION\n\nQuadratic equations can be solved using complete factor pairs.",
        ]],
        method: 'pdf_text',
    );
}

/** @return array<int, float> */
function reprocessingVector(float $first): array
{
    $vector = array_fill(0, 768, 0.0);
    $vector[0] = $first;

    return $vector;
}
