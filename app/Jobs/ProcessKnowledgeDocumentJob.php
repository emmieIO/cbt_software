<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\CurriculumMappingService;
use App\Services\DocumentTextExtractionService;
use App\Services\KnowledgeDocumentService;
use App\Services\TextChunkingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProcessKnowledgeDocumentJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 1800;

    public int $uniqueFor = 3600;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $documentId,
        public bool $reprocess = false,
    ) {}

    public function uniqueId(): string
    {
        return $this->documentId;
    }

    public function handle(
        DocumentTextExtractionService $documentTextExtractionService,
        TextChunkingService $textChunkingService,
    ): void {
        $document = KnowledgeDocument::query()->findOrFail($this->documentId);
        $isReprocessing = $this->reprocess || $this->hasUsableActiveIndex($document);

        $document->update($isReprocessing
            ? [
                'reprocessing_status' => KnowledgeDocument::REPROCESSING_PROCESSING,
                'reprocessing_error' => null,
            ]
            : [
                'processing_status' => KnowledgeDocument::PROCESSING_PROCESSING,
                'processing_error' => null,
            ]);

        try {
            $path = $this->verifiedPath($document);
            $extractedDocument = $documentTextExtractionService->extract($document, $path);
            $chunks = $textChunkingService->chunk($extractedDocument);

            if ($chunks === []) {
                throw new RuntimeException('No text chunks could be created from this document.');
            }

            $chunkVersion = DB::transaction(function () use ($chunks, $document, $extractedDocument, $isReprocessing): int {
                $lockedDocument = KnowledgeDocument::query()->lockForUpdate()->findOrFail($document->id);
                $chunkVersion = $isReprocessing
                    ? $lockedDocument->active_chunk_version + 1
                    : $lockedDocument->active_chunk_version;

                if ($isReprocessing) {
                    $lockedDocument->chunks()
                        ->where('chunk_version', '!=', $lockedDocument->active_chunk_version)
                        ->delete();
                } else {
                    $lockedDocument->chunks()->delete();
                }

                $lockedDocument->chunks()->createMany(array_map(
                    fn (array $chunk): array => [...$chunk, 'chunk_version' => $chunkVersion],
                    $chunks,
                ));

                $metadata = [
                    ...($lockedDocument->metadata ?? []),
                    'extraction_method' => $extractedDocument->method,
                    'extracted_at' => now()->toIso8601String(),
                    'chunk_count' => count($chunks),
                ];

                if ($isReprocessing) {
                    if ($lockedDocument->approval_status === KnowledgeDocument::APPROVAL_APPROVED) {
                        $lockedDocument->update([
                            'pending_chunk_version' => $chunkVersion,
                            'reprocessing_status' => KnowledgeDocument::REPROCESSING_EMBEDDING,
                            'reprocessing_error' => null,
                            'metadata' => $metadata,
                        ]);
                    } else {
                        $lockedDocument->update([
                            'active_chunk_version' => $chunkVersion,
                            'pending_chunk_version' => null,
                            'reprocessing_status' => null,
                            'reprocessing_error' => null,
                            'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
                            'processing_error' => null,
                            'embedding_status' => KnowledgeDocument::EMBEDDING_PENDING,
                            'embedding_provider' => null,
                            'embedding_model' => null,
                            'embedding_dimensions' => null,
                            'embedding_error' => null,
                            'embedded_at' => null,
                            'metadata' => $metadata,
                        ]);
                        app(CurriculumMappingService::class)->revalidate($lockedDocument);
                        $lockedDocument->update(['topic_detection_status' => 'pending', 'topic_detection_version' => null, 'topic_detection_cursor' => 0]);
                        $lockedDocument->chunks()->where('chunk_version', '!=', $chunkVersion)->delete();
                    }
                } else {
                    $lockedDocument->update([
                        'topic_detection_status' => 'pending', 'topic_detection_version' => null, 'topic_detection_cursor' => 0,
                        'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
                        'processing_error' => null,
                        'embedding_status' => KnowledgeDocument::EMBEDDING_PENDING,
                        'embedding_provider' => null,
                        'embedding_model' => null,
                        'embedding_dimensions' => null,
                        'embedding_error' => null,
                        'embedded_at' => null,
                        'metadata' => $metadata,
                    ]);
                    app(CurriculumMappingService::class)->revalidate($lockedDocument);
                }

                return $chunkVersion;
            });

            $document->refresh();

            if ($document->approval_status === KnowledgeDocument::APPROVAL_APPROVED) {
                if (! app(KnowledgeDocumentService::class)->queueEmbedding($document, $chunkVersion)) {
                    throw new RuntimeException('Document embedding could not be queued. Check the queue service and retry.');
                }
            }
        } catch (Throwable $exception) {
            $this->markAsFailed($exception, $isReprocessing);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            $document = KnowledgeDocument::query()->find($this->documentId);
            $this->markAsFailed($exception, $this->reprocess || ($document !== null && $this->hasUsableActiveIndex($document)));
        }
    }

    private function verifiedPath(KnowledgeDocument $document): string
    {
        $disk = Storage::disk($document->disk);

        if (! $disk->exists($document->path)) {
            throw new RuntimeException('The original document file could not be found in private storage.');
        }

        $path = $disk->path($document->path);
        $checksum = hash_file('sha256', $path);

        if ($checksum === false || ! hash_equals($document->checksum, $checksum)) {
            throw new RuntimeException('The original document failed checksum verification.');
        }

        return $path;
    }

    private function markAsFailed(Throwable $exception, bool $isReprocessing): void
    {
        KnowledgeDocument::query()
            ->whereKey($this->documentId)
            ->update($isReprocessing
                ? [
                    'reprocessing_status' => KnowledgeDocument::REPROCESSING_FAILED,
                    'reprocessing_error' => Str::limit($exception->getMessage(), 2000, ''),
                ]
                : [
                    'processing_status' => KnowledgeDocument::PROCESSING_FAILED,
                    'processing_error' => Str::limit($exception->getMessage(), 2000, ''),
                ]);
    }

    private function hasUsableActiveIndex(KnowledgeDocument $document): bool
    {
        return $document->processing_status === KnowledgeDocument::PROCESSING_COMPLETED
            && $document->approval_status === KnowledgeDocument::APPROVAL_APPROVED
            && $document->embedding_status === KnowledgeDocument::EMBEDDING_COMPLETED
            && $document->chunks()->where('chunk_version', $document->active_chunk_version)->whereNotNull('embedding')->exists();
    }
}
