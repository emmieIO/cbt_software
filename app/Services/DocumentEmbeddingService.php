<?php

namespace App\Services;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentEmbeddingService
{
    public const STORED_DIMENSIONS = 768;

    public function __construct(private readonly OllamaEmbeddingService $ollamaEmbeddingService) {}

    public function embed(KnowledgeDocument $document, ?int $chunkVersion = null): void
    {
        $this->ensureDocumentCanBeEmbedded($document);
        $configuration = $this->configuration();
        $chunkVersion ??= $document->pending_chunk_version ?? $document->active_chunk_version;

        $chunks = $document->chunks()
            ->where('chunk_version', $chunkVersion)
            ->orderBy('sequence')
            ->get();

        if ($chunks->isEmpty()) {
            throw new RuntimeException('The document has no extracted chunks to embed.');
        }

        foreach ($chunks as $chunk) {
            if ($this->hasCurrentEmbedding($chunk, $configuration)) {
                continue;
            }

            $freshDocument = KnowledgeDocument::query()->findOrFail($document->id);
            $this->ensureDocumentCanBeEmbedded($freshDocument);
            $embedding = $this->ollamaEmbeddingService->embed($chunk->content);

            DB::transaction(function () use ($chunk, $configuration, $document, $embedding): void {
                $lockedDocument = KnowledgeDocument::query()->lockForUpdate()->findOrFail($document->id);
                $this->ensureDocumentCanBeEmbedded($lockedDocument);
                $lockedChunk = KnowledgeDocumentChunk::query()->lockForUpdate()->findOrFail($chunk->id);
                $originalHash = $chunk->content_hash ?? hash('sha256', $chunk->content);
                $currentHash = $lockedChunk->content_hash ?? hash('sha256', $lockedChunk->content);

                if (! hash_equals($originalHash, $currentHash)) {
                    throw new RuntimeException('A document chunk changed while its embedding was being generated.');
                }

                $lockedChunk->update([
                    'embedding' => $embedding,
                    'embedding_provider' => $configuration['provider'],
                    'embedding_model' => $configuration['model'],
                    'embedding_dimensions' => $configuration['dimensions'],
                    'embedded_content_hash' => $currentHash,
                    'embedded_at' => now(),
                ]);
            });
        }

        $hasInvalidChunk = $document->chunks()->where('chunk_version', $chunkVersion)->get()->contains(
            fn (KnowledgeDocumentChunk $chunk): bool => ! $this->hasCurrentEmbedding($chunk, $configuration),
        );

        if ($hasInvalidChunk) {
            throw new RuntimeException('Not every document chunk has a valid embedding.');
        }
    }

    /** @return array{provider: string, model: string, dimensions: int} */
    public function configuration(): array
    {
        $configuration = [
            'provider' => (string) config('knowledge.embeddings.provider'),
            'model' => (string) config('knowledge.embeddings.model'),
            'dimensions' => (int) config('knowledge.embeddings.dimensions'),
        ];

        if ($configuration['provider'] === '' || $configuration['model'] === '') {
            throw new RuntimeException('The knowledge embedding provider and model must be configured.');
        }

        if ($configuration['dimensions'] !== self::STORED_DIMENSIONS) {
            throw new RuntimeException('Knowledge embeddings must use 768 dimensions to match the database vector column.');
        }

        return $configuration;
    }

    /** @param array{provider: string, model: string, dimensions: int} $configuration */
    private function hasCurrentEmbedding(KnowledgeDocumentChunk $chunk, array $configuration): bool
    {
        $contentHash = $chunk->content_hash ?? hash('sha256', $chunk->content);

        return is_array($chunk->embedding)
            && count($chunk->embedding) === $configuration['dimensions']
            && $chunk->embedding_provider === $configuration['provider']
            && $chunk->embedding_model === $configuration['model']
            && $chunk->embedding_dimensions === $configuration['dimensions']
            && $chunk->embedded_content_hash === $contentHash;
    }

    private function ensureDocumentCanBeEmbedded(KnowledgeDocument $document): void
    {
        if ($document->processing_status !== KnowledgeDocument::PROCESSING_COMPLETED) {
            throw new RuntimeException('Only fully processed documents can be embedded.');
        }

        if ($document->approval_status !== KnowledgeDocument::APPROVAL_APPROVED) {
            throw new RuntimeException('Unapproved document content cannot be sent to the embedding provider.');
        }
    }
}
