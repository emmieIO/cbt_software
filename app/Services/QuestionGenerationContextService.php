<?php

namespace App\Services;

use App\Models\KnowledgeDocumentChunk;
use App\Models\QuestionGenerationRun;
use App\Support\AcademicLevels;
use Illuminate\Support\Collection;
use RuntimeException;

class QuestionGenerationContextService
{
    public function __construct(
        private readonly KnowledgeRetrievalService $retrievalService,
        private readonly CurriculumMappingService $mappingService,
    ) {}

    /**
     * @return array{query: string, context: string, sources: array<int, array<string, mixed>>}
     */
    public function build(QuestionGenerationRun $run): array
    {
        $run->loadMissing(['subject:id,name', 'topic:id,name', 'subtopic:id,name,topic_id']);
        if (($run->subtopic_name && ! $run->subtopic_id) || ($run->subtopic_id && $run->subtopic?->topic_id !== $run->topic_id)) {
            throw new RuntimeException('The selected subtopic no longer belongs to this topic.');
        }
        $chunkIds = $this->mappingService->chunkIds($run->subject_id, $run->class_level, $run->topic_id, $run->subtopic_id);
        if ($chunkIds === []) {
            throw new RuntimeException('There is not enough approved knowledge with confirmed curriculum coverage for this subject, class and topic. Review the document mappings first.');
        }
        $classLabel = AcademicLevels::classLabel($run->class_level, $run->level) ?? $run->class_level;
        $query = trim(implode('. ', array_filter([
            $run->subject?->name,
            $run->topic?->name,
            $run->subtopic_name,
            $classLabel,
            $run->additional_instructions,
        ])));

        $chunks = $this->retrievalService->search(
            $query,
            (int) config('knowledge.generation.retrieval_limit', 12),
            chunkIds: $chunkIds,
            curriculum: ['subject_id' => $run->subject_id, 'class_level' => $run->class_level, 'topic_id' => $run->topic_id, 'subtopic_id' => $run->subtopic_id],
        )->filter(fn (KnowledgeDocumentChunk $chunk): bool => (float) $chunk->getAttribute('similarity') >= (float) config('knowledge.generation.minimum_similarity', 0.65)
        );

        $passages = $this->mappingService->coverage($run->subject_id, $run->class_level, $run->topic_id, $run->subtopic_id)
            ->flatMap(fn ($mapping) => $mapping->evidence)->groupBy('knowledge_document_chunk_id');
        $chunks = $chunks->filter(function (KnowledgeDocumentChunk $chunk) use ($passages): bool {
            $quotes = $passages->get($chunk->id, collect())->pluck('excerpt')->unique()->values();
            if ($quotes->isEmpty()) {
                return false;
            }
            $chunk->content = $quotes->implode("\n\n");
            $chunk->content_hash = hash('sha256', $chunk->content);
            $chunk->estimated_token_count = (int) ceil(mb_strlen($chunk->content) / 4);

            return true;
        });
        $sources = $this->selectSources($chunks);
        if ($sources === []) {
            throw new RuntimeException('There is not enough approved knowledge for this subject and topic. Approve and embed a relevant document, or broaden the topic, then try again.');
        }

        $context = collect($sources)->map(function (array $source): string {
            $page = $source['page_start'] === $source['page_end']
                ? 'page '.$source['page_start']
                : 'pages '.$source['page_start'].'-'.$source['page_end'];

            return "[SOURCE {$source['chunk_id']} | {$source['document_title']} | {$page}]\n{$source['content']}";
        })->implode("\n\n");

        return compact('query', 'context', 'sources');
    }

    /**
     * @param  Collection<int, KnowledgeDocumentChunk>  $chunks
     * @return array<int, array<string, mixed>>
     */
    private function selectSources(Collection $chunks): array
    {
        $budget = (int) config('knowledge.generation.context_token_budget', 6000);
        $used = 0;
        $hashes = [];
        $sources = [];

        foreach ($chunks as $chunk) {
            $hash = $chunk->content_hash ?: hash('sha256', trim($chunk->content));
            if (isset($hashes[$hash])) {
                continue;
            }

            $tokens = max(1, $chunk->estimated_token_count ?? (int) ceil(mb_strlen($chunk->content) / 4));
            if ($used + $tokens > $budget && $sources !== []) {
                continue;
            }

            $metadata = $chunk->metadata ?? [];
            $sources[] = [
                'chunk_id' => $chunk->id,
                'chunk_version' => $chunk->chunk_version,
                'content_hash' => $chunk->getRawOriginal('content_hash'),
                'document_id' => $chunk->knowledge_document_id,
                'document_title' => $chunk->document?->title ?? 'Untitled document',
                'original_name' => $chunk->document?->original_name,
                'page_start' => (int) ($metadata['page_start'] ?? $metadata['page'] ?? 1),
                'page_end' => (int) ($metadata['page_end'] ?? $metadata['page'] ?? 1),
                'similarity' => round((float) $chunk->getAttribute('similarity'), 6),
                'content' => $chunk->content,
            ];
            $hashes[$hash] = true;
            $used += $tokens;

            if ($used >= $budget) {
                break;
            }
        }

        return $sources;
    }
}
