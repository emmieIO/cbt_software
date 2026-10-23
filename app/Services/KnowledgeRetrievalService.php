<?php

namespace App\Services;

use App\Models\CurriculumTopicEvidence;
use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KnowledgeRetrievalService
{
    public function __construct(
        private readonly DocumentEmbeddingService $documentEmbeddingService,
        private readonly OllamaEmbeddingService $ollamaEmbeddingService,
    ) {}

    /**
     * @param  array<int, string>  $documentIds
     * @return Collection<int, KnowledgeDocumentChunk>
     */
    public function search(string $query, ?int $limit = null, array $documentIds = [], ?array $chunkIds = null, ?array $curriculum = null): Collection
    {
        if ($chunkIds === []) {
            return new Collection;
        }
        $query = trim($query);

        if ($query === '') {
            throw new RuntimeException('A search query is required.');
        }

        $configuration = $this->documentEmbeddingService->configuration();
        $threshold = (float) config('knowledge.retrieval.similarity_threshold', 0.65);
        $candidateCount = $limit ?? (int) config('knowledge.retrieval.candidate_count', 10);

        if ($threshold < 0 || $threshold > 1 || $candidateCount < 1 || $candidateCount > 100) {
            throw new RuntimeException('Knowledge retrieval configuration is invalid.');
        }

        $embedding = $this->ollamaEmbeddingService->embed($query);

        if (DB::getDriverName() === 'pgsql') {
            return $this->searchPostgres($embedding, $documentIds, $configuration, $threshold, $candidateCount, $chunkIds, $curriculum);
        }

        if (DB::getDriverName() === 'sqlite' && app()->runningUnitTests()) {
            return $this->searchInMemory($embedding, $documentIds, $configuration, $threshold, $candidateCount, $chunkIds, $curriculum);
        }

        throw new RuntimeException('Semantic retrieval requires PostgreSQL with pgvector.');
    }

    /**
     * @param  array<int, float>  $embedding
     * @param  array<int, string>  $documentIds
     * @param  array{provider: string, model: string, dimensions: int}  $configuration
     * @return Collection<int, KnowledgeDocumentChunk>
     */
    private function searchPostgres(array $embedding, array $documentIds, array $configuration, float $threshold, int $candidateCount, ?array $chunkIds, ?array $curriculum): Collection
    {
        $chunks = $this->eligibleChunks($documentIds, $configuration, $chunkIds, $curriculum)
            ->select('knowledge_document_chunks.*')
            ->selectVectorDistance('embedding', $embedding, 'distance')
            ->whereVectorSimilarTo('embedding', $embedding, $threshold)
            ->orderBy('distance')
            ->limit($candidateCount)
            ->get();

        return $chunks->each(function (KnowledgeDocumentChunk $chunk): void {
            $distance = (float) $chunk->getAttribute('distance');
            $chunk->setAttribute('distance', $distance);
            $chunk->setAttribute('similarity', 1 - $distance);
        });
    }

    /**
     * @param  array<int, float>  $embedding
     * @param  array<int, string>  $documentIds
     * @param  array{provider: string, model: string, dimensions: int}  $configuration
     * @return Collection<int, KnowledgeDocumentChunk>
     */
    private function searchInMemory(array $embedding, array $documentIds, array $configuration, float $threshold, int $candidateCount, ?array $chunkIds, ?array $curriculum): Collection
    {
        return $this->eligibleChunks($documentIds, $configuration, $chunkIds, $curriculum)
            ->get()
            ->map(function (KnowledgeDocumentChunk $chunk) use ($embedding): KnowledgeDocumentChunk {
                $similarity = $this->cosineSimilarity($embedding, $chunk->embedding ?? []);
                $chunk->setAttribute('distance', 1 - $similarity);
                $chunk->setAttribute('similarity', $similarity);

                return $chunk;
            })
            ->filter(fn (KnowledgeDocumentChunk $chunk): bool => (float) $chunk->getAttribute('similarity') >= $threshold)
            ->sortByDesc(fn (KnowledgeDocumentChunk $chunk): float => (float) $chunk->getAttribute('similarity'))
            ->take($candidateCount)
            ->values();
    }

    /**
     * @param  array<int, string>  $documentIds
     * @param  array{provider: string, model: string, dimensions: int}  $configuration
     */
    private function eligibleChunks(array $documentIds, array $configuration, ?array $chunkIds = null, ?array $curriculum = null): Builder
    {
        return KnowledgeDocumentChunk::query()
            ->with('document:id,title,document_type,original_name')
            ->whereNotNull('embedding')
            ->where('embedding_provider', $configuration['provider'])
            ->where('embedding_model', $configuration['model'])
            ->where('embedding_dimensions', $configuration['dimensions'])
            ->when($chunkIds !== null, fn ($query) => $query->whereIn('id', $chunkIds))
            ->when($curriculum !== null, fn ($query) => $query->whereIn('id',
                CurriculumTopicEvidence::query()->select('knowledge_document_chunk_id')
                    ->whereIn('curriculum_topic_mapping_id', CurriculumTopicMapping::query()->eligible()->select('id')
                        ->where('subject_id', $curriculum['subject_id'])
                        ->where('class_level', $curriculum['class_level'])
                        ->where('topic_id', $curriculum['topic_id'])
                        ->when($curriculum['subtopic_id'] ?? null, fn ($q, $id) => $q->where('subtopic_id', $id)))))
            ->when($documentIds !== [], fn ($query) => $query->whereIn('knowledge_document_id', $documentIds))
            ->whereHas('document', fn ($query) => $query
                ->whereColumn('knowledge_documents.active_chunk_version', 'knowledge_document_chunks.chunk_version')
                ->where('processing_status', KnowledgeDocument::PROCESSING_COMPLETED)
                ->where('approval_status', KnowledgeDocument::APPROVAL_APPROVED)
                ->where('embedding_status', KnowledgeDocument::EMBEDDING_COMPLETED)
                ->where('embedding_provider', $configuration['provider'])
                ->where('embedding_model', $configuration['model'])
                ->where('embedding_dimensions', $configuration['dimensions']));
    }

    /** @param array<int, float> $left @param array<int, float> $right */
    private function cosineSimilarity(array $left, array $right): float
    {
        if (count($left) !== count($right) || $left === []) {
            return -1.0;
        }

        $dotProduct = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        foreach ($left as $index => $value) {
            $rightValue = $right[$index];
            $dotProduct += $value * $rightValue;
            $leftMagnitude += $value ** 2;
            $rightMagnitude += $rightValue ** 2;
        }

        if ($leftMagnitude === 0.0 || $rightMagnitude === 0.0) {
            return -1.0;
        }

        return $dotProduct / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }
}
