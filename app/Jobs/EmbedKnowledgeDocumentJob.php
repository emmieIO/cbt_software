<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\CurriculumMappingService;
use App\Services\DocumentEmbeddingService;
use App\Services\KnowledgeDocumentService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EmbedKnowledgeDocumentJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 900;

    public int $uniqueFor = 1800;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $documentId,
        public ?int $chunkVersion = null,
    ) {}

    public function uniqueId(): string
    {
        return $this->documentId.':'.($this->chunkVersion ?? 'active');
    }

    public function handle(DocumentEmbeddingService $documentEmbeddingService): void
    {
        $document = KnowledgeDocument::query()->findOrFail($this->documentId);
        $configuration = $documentEmbeddingService->configuration();
        $chunkVersion = $this->chunkVersion ?? $document->pending_chunk_version ?? $document->active_chunk_version;
        if (! in_array($chunkVersion, [$document->active_chunk_version, $document->pending_chunk_version], true)) {
            return;
        }
        $isReprocessing = $document->pending_chunk_version === $chunkVersion
            && $chunkVersion !== $document->active_chunk_version;

        $started = KnowledgeDocument::query()->whereKey($document->id)
            ->where($isReprocessing ? 'pending_chunk_version' : 'active_chunk_version', $chunkVersion)
            ->update($isReprocessing
            ? [
                'reprocessing_status' => KnowledgeDocument::REPROCESSING_EMBEDDING,
                'reprocessing_error' => null,
            ]
            : [
                'embedding_status' => KnowledgeDocument::EMBEDDING_PROCESSING,
                'embedding_provider' => $configuration['provider'],
                'embedding_model' => $configuration['model'],
                'embedding_dimensions' => $configuration['dimensions'],
                'embedding_error' => null,
                'embedded_at' => null,
            ]);
        if ($started === 0) {
            return;
        }

        try {
            $documentEmbeddingService->embed($document->refresh(), $chunkVersion);

            if ($isReprocessing) {
                $this->activateStagedVersion($chunkVersion, $configuration);
            } else {
                KnowledgeDocument::query()->whereKey($document->id)->where('active_chunk_version', $chunkVersion)->update([
                    'embedding_status' => KnowledgeDocument::EMBEDDING_COMPLETED,
                    'embedding_error' => null,
                    'embedded_at' => now(),
                ]);
            }
        } catch (Throwable $exception) {
            $this->markAsFailed($exception, $isReprocessing, $chunkVersion);
            Log::warning('Knowledge document embedding attempt failed.', [
                'document_id' => $this->documentId,
                'chunk_version' => $chunkVersion,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $document->refresh();
        if ($document->topic_detection_version !== $document->active_chunk_version || $document->topic_detection_status === 'pending') {
            app(KnowledgeDocumentService::class)->queueTopicDetection($document);
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            $document = KnowledgeDocument::query()->find($this->documentId);
            $isReprocessing = $document !== null
                && $this->chunkVersion !== null
                && $document->pending_chunk_version === $this->chunkVersion
                && $this->chunkVersion !== $document->active_chunk_version;

            if ($this->chunkVersion !== null) {
                $this->markAsFailed($exception, $isReprocessing, $this->chunkVersion);
            }
            Log::error('Knowledge document embedding failed permanently.', [
                'document_id' => $this->documentId,
                'chunk_version' => $this->chunkVersion,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /** @param array{provider: string, model: string, dimensions: int} $configuration */
    private function activateStagedVersion(int $chunkVersion, array $configuration): void
    {
        DB::transaction(function () use ($chunkVersion, $configuration): void {
            $document = KnowledgeDocument::query()->lockForUpdate()->findOrFail($this->documentId);

            if ($document->pending_chunk_version !== $chunkVersion) {
                throw new RuntimeException('The staged chunk version is no longer current.');
            }

            $document->update([
                'active_chunk_version' => $chunkVersion,
                'pending_chunk_version' => null,
                'reprocessing_status' => null,
                'reprocessing_error' => null,
                'processing_status' => KnowledgeDocument::PROCESSING_COMPLETED,
                'processing_error' => null,
                'embedding_status' => KnowledgeDocument::EMBEDDING_COMPLETED,
                'embedding_provider' => $configuration['provider'],
                'embedding_model' => $configuration['model'],
                'embedding_dimensions' => $configuration['dimensions'],
                'embedding_error' => null,
                'embedded_at' => now(),
            ]);

            app(CurriculumMappingService::class)->revalidate($document);
            $document->update(['topic_detection_status' => 'pending', 'topic_detection_error' => null, 'topic_detection_cursor' => 0, 'topic_detection_version' => null]);
            $document->chunks()->where('chunk_version', '!=', $chunkVersion)->delete();
        });
    }

    private function markAsFailed(Throwable $exception, bool $isReprocessing, int $chunkVersion): void
    {
        KnowledgeDocument::query()
            ->whereKey($this->documentId)
            ->where($isReprocessing ? 'pending_chunk_version' : 'active_chunk_version', $chunkVersion)
            ->update($isReprocessing
                ? [
                    'reprocessing_status' => KnowledgeDocument::REPROCESSING_FAILED,
                    'reprocessing_error' => Str::limit($exception->getMessage(), 2000, ''),
                ]
                : [
                    'embedding_status' => KnowledgeDocument::EMBEDDING_FAILED,
                    'embedding_error' => Str::limit($exception->getMessage(), 2000, ''),
                    'embedded_at' => null,
                ]);
    }
}
