<?php

use App\Jobs\EmbedKnowledgeDocumentJob;
use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\User;
use App\Services\DocumentTextExtractionService;
use App\Services\ExtractedDocumentText;
use App\Services\PdfImportService;
use App\Services\PdfOcrService;
use App\Services\PdfTextExtractor;
use App\Services\TextChunkingService;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\mock;

test('pdf text extraction uses embedded text without ocr', function () {
    Process::fake();
    $path = knowledgePdfPath('<h1>Quadratic Equations</h1><p>Factorise x squared plus five x plus six.</p>');

    $result = app(PdfTextExtractor::class)->extract($path);

    expect($result->method)->toBe('pdf_text')
        ->and($result->text)->toContain('Quadratic Equations')
        ->and($result->pages)->not->toBeEmpty()
        ->and($result->pages[0]['number'])->toBe(1);

    Process::assertNothingRan();
});

test('pdf text extraction falls back to tesseract and retains page metadata', function () {
    Process::fake(function (PendingProcess $process) {
        $command = $process->command;

        if (is_array($command) && str_ends_with($command[0], 'pdftoppm')) {
            file_put_contents($command[array_key_last($command)].'-1.png', 'page one');
            file_put_contents($command[array_key_last($command)].'-2.png', 'page two');

            return Process::result();
        }

        if (is_array($command) && str_ends_with($command[0], 'tesseract')) {
            $page = str_contains($command[1], 'page-1') ? 1 : 2;

            return Process::result(output: "Recognized curriculum page {$page}");
        }

        return Process::result(exitCode: 1);
    });
    $path = knowledgePdfPath('');

    $result = app(PdfTextExtractor::class)->extract($path);

    expect($result->method)->toBe('ocr')
        ->and($result->pages)->toBe([
            ['number' => 1, 'text' => 'Recognized curriculum page 1'],
            ['number' => 2, 'text' => 'Recognized curriculum page 2'],
        ])
        ->and($result->text)->toContain('--- Page 2 ---');

    Process::assertRan(fn (PendingProcess $process) => is_array($process->command)
        && str_ends_with($process->command[0], 'pdftoppm'));
    Process::assertRan(fn (PendingProcess $process) => is_array($process->command)
        && str_ends_with($process->command[0], 'tesseract'));
});

test('ocr probes beyond the page cap before recognizing any pages', function (int $pageCount) {
    config(['services.pdf_ocr.enabled' => true, 'services.pdf_ocr.max_pages' => '3']);
    $directory = null;
    Process::fake(function (PendingProcess $process) use ($pageCount, &$directory) {
        $command = $process->command;

        if (is_array($command) && str_ends_with($command[0], 'pdftoppm')) {
            $prefix = $command[array_key_last($command)];
            $directory = dirname($prefix);
            $renderLimit = (int) $command[array_search('-l', $command, true) + 1];

            for ($page = 1; $page <= min($pageCount, $renderLimit); $page++) {
                File::put($prefix.'-'.$page.'.png', 'page image');
            }

            return Process::result();
        }

        return Process::result(output: basename($command[1], '.png'));
    });

    if ($pageCount > 3) {
        expect(fn () => app(PdfOcrService::class)->extractPages('/scanned.pdf'))
            ->toThrow(RuntimeException::class, 'exceeds the OCR limit of 3 pages');

        Process::assertNotRan(fn (PendingProcess $process): bool => str_ends_with($process->command[0], 'tesseract'));
    } else {
        expect(app(PdfOcrService::class)->extractPages('/scanned.pdf'))->toBe(
            array_map(fn (int $page): array => ['number' => $page, 'text' => 'page-'.$page], range(1, $pageCount)),
        );

        Process::assertRanTimes(fn (PendingProcess $process): bool => str_ends_with($process->command[0], 'tesseract'), $pageCount);
    }

    Process::assertRan(fn (PendingProcess $process): bool => str_ends_with($process->command[0], 'pdftoppm')
        && $process->command[array_search('-l', $process->command, true) + 1] === '4');
    expect($directory)->not->toBeNull()
        ->and(File::exists($directory))->toBeFalse();
})->with([
    'below cap' => [2],
    'exact cap' => [3],
    'one page over cap' => [4],
    'well over cap' => [8],
]);

test('ocr rejects invalid page caps before starting a process', function (mixed $maxPages) {
    config(['services.pdf_ocr.enabled' => true, 'services.pdf_ocr.max_pages' => $maxPages]);
    Process::fake();
    File::ensureDirectoryExists(storage_path('app/private/tmp/pdf-ocr'));
    $directories = File::directories(storage_path('app/private/tmp/pdf-ocr'));

    expect(fn () => app(PdfOcrService::class)->extractPages('/scanned.pdf'))
        ->toThrow(RuntimeException::class, 'PDF_OCR_MAX_PAGES must be a positive integer');

    Process::assertNothingRan();
    expect(File::directories(storage_path('app/private/tmp/pdf-ocr')))->toBe($directories);
})->with([
    'zero' => [0],
    'negative' => [-1],
    'fractional' => [1.5],
    'fractional string' => ['1.5'],
    'non-numeric' => ['invalid'],
    'empty' => [''],
    'null' => [null],
    'boolean' => [true],
    'probe overflow' => [PHP_INT_MAX],
    'integer overflow' => [(string) PHP_INT_MAX.'0'],
]);

test('pdf import rejects oversized scans without recognizing or partially importing pages', function () {
    config(['services.pdf_ocr.enabled' => true, 'services.pdf_ocr.max_pages' => 1]);
    $directory = null;
    Process::fake(function (PendingProcess $process) use (&$directory) {
        $command = $process->command;

        if (is_array($command) && str_ends_with($command[0], 'pdftoppm')) {
            $prefix = $command[array_key_last($command)];
            $directory = dirname($prefix);
            File::put($prefix.'-1.png', 'page one');
            File::put($prefix.'-2.png', 'page two');
        }

        return Process::result();
    });
    $path = knowledgePdfPath('');

    try {
        $file = new UploadedFile($path, 'oversized-scan.pdf', 'application/pdf', null, true);

        expect(fn () => app(PdfImportService::class)->buildPreview($file))
            ->toThrow(RuntimeException::class, 'exceeds the OCR limit of 1 pages');

        Process::assertNotRan(fn (PendingProcess $process): bool => str_ends_with($process->command[0], 'tesseract'));
        expect($directory)->not->toBeNull()
            ->and(File::exists($directory))->toBeFalse();
    } finally {
        File::delete($path);
    }
});

test('the processing job verifies the source and persists chunks with completed status', function () {
    Storage::fake('local');
    $document = storedKnowledgeDocument('verified source content');

    $extractor = mock(DocumentTextExtractionService::class);
    $extractor->shouldReceive('extract')
        ->once()
        ->andReturnUsing(function (KnowledgeDocument $processingDocument): ExtractedDocumentText {
            expect($processingDocument->fresh()->processing_status)->toBe(KnowledgeDocument::PROCESSING_PROCESSING);

            return extractedLessonText();
        });

    (new ProcessKnowledgeDocumentJob($document->id))->handle($extractor, new TextChunkingService);

    $document->refresh();
    $chunk = $document->chunks()->firstOrFail();

    expect($document->processing_status)->toBe(KnowledgeDocument::PROCESSING_COMPLETED)
        ->and($document->processing_error)->toBeNull()
        ->and($document->metadata['extraction_method'])->toBe('pdf_text')
        ->and($document->metadata['chunk_count'])->toBe($document->chunks()->count())
        ->and($chunk->content_hash)->toBe(hash('sha256', $chunk->content))
        ->and($chunk->estimated_token_count)->toBeGreaterThan(0)
        ->and($chunk->metadata)->toHaveKeys(['page_start', 'page_end']);
});

test('processing is idempotent and replaces existing chunks atomically', function () {
    Storage::fake('local');
    $document = storedKnowledgeDocument('idempotent source content');
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create([
        'sequence' => 99,
        'content' => 'Obsolete chunk',
        'content_hash' => hash('sha256', 'Obsolete chunk'),
    ]);

    $extractor = mock(DocumentTextExtractionService::class);
    $extractor->shouldReceive('extract')->twice()->andReturn(extractedLessonText());
    $job = new ProcessKnowledgeDocumentJob($document->id);

    $job->handle($extractor, new TextChunkingService);
    $firstHashes = $document->chunks()->orderBy('sequence')->pluck('content_hash')->all();

    $job->handle($extractor, new TextChunkingService);
    $secondHashes = $document->chunks()->orderBy('sequence')->pluck('content_hash')->all();

    expect($secondHashes)->toBe($firstHashes)
        ->and($secondHashes)->not->toContain(hash('sha256', 'Obsolete chunk'))
        ->and($document->chunks()->count())->toBe(count($firstHashes));
});

test('processing completion queues embeddings when a document is already approved', function () {
    Storage::fake('local');
    Queue::fake();
    $document = storedKnowledgeDocument('approved source content');
    $document->update([
        'approval_status' => KnowledgeDocument::APPROVAL_APPROVED,
        'approved_at' => now(),
    ]);
    $extractor = mock(DocumentTextExtractionService::class);
    $extractor->shouldReceive('extract')->once()->andReturn(extractedLessonText());

    (new ProcessKnowledgeDocumentJob($document->id))->handle($extractor, new TextChunkingService);

    Queue::assertPushed(EmbedKnowledgeDocumentJob::class, fn (EmbedKnowledgeDocumentJob $job): bool => $job->documentId === $document->id);
});

test('processing fails before extraction when the checksum does not match', function () {
    Storage::fake('local');
    $document = storedKnowledgeDocument('tampered content', hash('sha256', 'expected content'));
    KnowledgeDocumentChunk::factory()->for($document, 'document')->create();
    $extractor = mock(DocumentTextExtractionService::class);
    $extractor->shouldNotReceive('extract');

    expect(fn () => (new ProcessKnowledgeDocumentJob($document->id))->handle($extractor, new TextChunkingService))
        ->toThrow(RuntimeException::class, 'checksum verification');

    $document->refresh();

    expect($document->processing_status)->toBe(KnowledgeDocument::PROCESSING_FAILED)
        ->and($document->processing_error)->toContain('checksum verification')
        ->and($document->chunks()->count())->toBe(1);
});

test('authorized users can retry failed processing', function () {
    Queue::fake();
    $user = User::factory()->create([
        'permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE],
    ]);
    $document = KnowledgeDocument::factory()->for($user, 'uploader')->create([
        'processing_status' => KnowledgeDocument::PROCESSING_FAILED,
        'processing_error' => 'OCR failed.',
    ]);

    $this->actingAs($user)
        ->post(route('knowledge-documents.retry', $document))
        ->assertRedirect(route('knowledge-documents.show', $document));

    expect($document->refresh()->processing_status)->toBe(KnowledgeDocument::PROCESSING_PENDING)
        ->and($document->processing_error)->toBeNull();
    Queue::assertPushed(ProcessKnowledgeDocumentJob::class, fn (ProcessKnowledgeDocumentJob $job) => $job->documentId === $document->id);
});

test('retry processing is permission protected and limited to failed documents', function () {
    Queue::fake();
    $unauthorizedUser = User::factory()->create();
    $authorizedUser = User::factory()->create([
        'permissions' => [User::PERMISSION_MANAGE_KNOWLEDGE_BASE],
    ]);
    $failedDocument = KnowledgeDocument::factory()->create([
        'processing_status' => KnowledgeDocument::PROCESSING_FAILED,
    ]);
    $completedDocument = KnowledgeDocument::factory()->create([
        'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
    ]);

    $this->actingAs($unauthorizedUser)
        ->post(route('knowledge-documents.retry', $failedDocument))
        ->assertForbidden();

    $this->actingAs($authorizedUser)
        ->post(route('knowledge-documents.retry', $completedDocument))
        ->assertStatus(409);

    Queue::assertNothingPushed();
});

function knowledgePdfPath(string $html): string
{
    $path = tempnam(sys_get_temp_dir(), 'knowledge-pdf-').'.pdf';
    file_put_contents($path, DomPdf::loadHTML($html)->output());

    return $path;
}

function storedKnowledgeDocument(string $content, ?string $checksum = null): KnowledgeDocument
{
    $path = 'knowledge-documents/testing/source.pdf';
    Storage::disk('local')->put($path, $content);

    return KnowledgeDocument::factory()->create([
        'path' => $path,
        'size_bytes' => strlen($content),
        'checksum' => $checksum ?? hash('sha256', $content),
    ]);
}

function extractedLessonText(): ExtractedDocumentText
{
    $paragraph = implode(' ', array_map(fn (int $number): string => "lesson{$number}", range(1, 500)));

    return new ExtractedDocumentText(
        text: $paragraph,
        pages: [
            ['number' => 1, 'text' => $paragraph],
            ['number' => 2, 'text' => $paragraph],
        ],
        method: 'pdf_text',
    );
}
