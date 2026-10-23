<?php

namespace App\Services;

use App\Ai\Agents\CurriculumTopicAgent;
use App\Models\KnowledgeDocument;
use App\Models\Subject;
use App\Support\AcademicLevels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CurriculumTopicIdentificationService
{
    public function __construct(private readonly CurriculumMappingService $mappingService) {}

    /** Process bounded batches; completed batches are checkpoints for retries/continuations. */
    public function identify(KnowledgeDocument $document, int $version): void
    {
        $this->ensureEligible($document, $version);
        if ($document->topic_detection_version !== $version) {
            $document->update(['topic_detection_version' => $version, 'topic_detection_cursor' => 0]);
        }
        $document->update(['topic_detection_status' => 'processing', 'topic_detection_error' => null]);
        $limit = max(2000, min(32000, (int) config('knowledge.curriculum.batch_characters', 16000)));
        $maxRequests = max(1, min(5, (int) config('knowledge.curriculum.requests_per_job', 3)));
        for ($request = 0; $request < $maxRequests; $request++) {
            $document->refresh();
            $this->ensureEligible($document, $version);
            $chunks = $document->chunks()->where('chunk_version', $version)
                ->where('sequence', '>', $document->topic_detection_cursor)->orderBy('sequence')->limit(12)->get();
            if ($chunks->isEmpty()) {
                $this->finalize($document, $version);

                return;
            }
            $batch = [];
            $size = 0;
            foreach ($chunks as $chunk) {
                if ($size + mb_strlen($chunk->content) > $limit && $batch !== []) {
                    break;
                }
                if (mb_strlen($chunk->content) > $limit) {
                    throw new RuntimeException('A source chunk exceeds the configured topic context limit. Increase the batch character limit or add its mappings manually.');
                }
                $content = $chunk->content;
                $batch[] = ['id' => $chunk->id, 'sequence' => $chunk->sequence, 'content' => $content];
                $size += mb_strlen($content);
            }
            $response = CurriculumTopicAgent::make()->prompt(
                'Untrusted document title: '.$document->title."\nDocument opening (context only, not citable): ".mb_substr((string) $document->chunks()->where('chunk_version', $version)->orderBy('sequence')->value('content'), 0, 1600)."\nIdentify curriculum hierarchy in these untrusted source passages:\n".json_encode($batch, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                timeout: (int) config('knowledge.curriculum.timeout', 120),
            );
            $suggestions = $response['topics'] ?? null;
            if (! is_array($suggestions) || count($suggestions) > 50) {
                throw new RuntimeException('The topic detector returned an invalid response.');
            }
            DB::transaction(function () use ($document, $version, $batch, $suggestions): void {
                $locked = KnowledgeDocument::query()->lockForUpdate()->findOrFail($document->id);
                $this->ensureEligible($locked, $version);
                foreach ($suggestions as $suggestion) {
                    $this->storeSuggestion($locked, $suggestion, array_column($batch, 'id'));
                }
                $locked->update(['topic_detection_cursor' => max(array_column($batch, 'sequence'))]);
            });
        }
        $this->finalize($document, $version);
    }

    private function finalize(KnowledgeDocument $document, int $version): void
    {
        DB::transaction(function () use ($document, $version): void {
            $locked = KnowledgeDocument::query()->lockForUpdate()->findOrFail($document->id);
            $this->ensureEligible($locked, $version);
            $hasMore = $locked->chunks()->where('chunk_version', $version)->where('sequence', '>', $locked->topic_detection_cursor)->exists();
            $locked->update(['topic_detection_status' => $hasMore ? 'partial' : 'completed']);
        });
    }

    /** @param mixed $suggestion @param array<int, string> $allowedIds */
    private function storeSuggestion(KnowledgeDocument $document, mixed $suggestion, array $allowedIds): void
    {
        if (! is_array($suggestion) || ! is_string($suggestion['topic_name'] ?? null)
            || trim($suggestion['topic_name']) === '' || mb_strlen($suggestion['topic_name']) > 255
            || ! is_array($suggestion['evidence'] ?? null) || $suggestion['evidence'] === []) {
            throw new RuntimeException('A detected topic is missing its name or supporting evidence.');
        }
        foreach ($suggestion['evidence'] as $item) {
            if (! is_array($item) || ! in_array($item['chunk_id'] ?? null, $allowedIds, true) || ! is_string($item['excerpt'] ?? null)) {
                throw new RuntimeException('The topic detector cited an unknown source chunk.');
            }
        }
        $evidence = $this->mappingService->validatedEvidence($document, $suggestion['evidence']);
        $class = in_array($suggestion['class_level'] ?? null, AcademicLevels::classValues(), true) ? $suggestion['class_level'] : null;
        $subjectName = is_string($suggestion['subject_name'] ?? null) ? mb_substr(trim($suggestion['subject_name']), 0, 255) : null;
        $subjectMatches = Subject::query()->get()->filter(fn (Subject $subject): bool => $subjectName !== null
            && $this->mappingService->normalizedName($subject->name) === $this->mappingService->normalizedName($subjectName)
            && ($class === null || AcademicLevels::classBelongsToLevel($class, $subject->level)));
        $subject = $subjectMatches->count() === 1 ? $subjectMatches->first() : null;
        $topicName = trim($suggestion['topic_name']);
        $classification = $suggestion['classification'] ?? 'topic';
        if (! in_array($classification, ['topic', 'subtopic', 'section'], true)) {
            throw new RuntimeException('The detector returned an invalid hierarchy classification.');
        }
        $parent = is_string($suggestion['parent_topic_name'] ?? null) ? mb_substr(trim($suggestion['parent_topic_name']), 0, 255) : '';
        $uncertain = ($suggestion['hierarchy_uncertain'] ?? false) || ($classification !== 'topic' && $parent === '');
        if (preg_match('/^(common misconceptions?|learning objectives?|worked examples?|guided practice|source notes?|application\s*:)/i', $topicName)) {
            $classification = 'section';
            $uncertain = $parent === '';
        }
        $topic = $subject && $class ? $this->mappingService->matchTopic($classification === 'topic' ? $topicName : $parent, $subject->id, $class) : null;
        $key = $this->mappingService->identity($subject?->id ?? $this->mappingService->normalizedName($subjectName ?? ''), $class, $classification, $classification === 'topic' ? '' : $parent, $topicName);
        $existing = $document->curriculumMappings()->get()->first(fn ($mapping): bool => $mapping->identity_key === $key || (
            ($mapping->subject_id === $subject?->id || ($mapping->subject_id === null && $subject !== null
                && $this->mappingService->normalizedName($mapping->detected_subject ?? '') === $this->mappingService->normalizedName($subjectName ?? ''))) && $mapping->class_level === $class
            && ($subject !== null || $this->mappingService->normalizedName($mapping->detected_subject ?? '') === $this->mappingService->normalizedName($subjectName ?? ''))
            && $mapping->classification === $classification
            && $this->mappingService->normalizedName($mapping->detected_parent_topic ?? '') === $this->mappingService->normalizedName($classification === 'topic' ? '' : $parent)
            && $this->mappingService->normalizedName($mapping->detected_topic) === $this->mappingService->normalizedName($topicName)
        ));
        $decided = $document->curriculumMappings()->whereNotNull('reviewed_at')->get()->contains(fn ($mapping): bool => ($mapping->subject_id === $subject?->id || ($mapping->subject_id === null && $subject !== null
            && $this->mappingService->normalizedName($mapping->detected_subject ?? '') === $this->mappingService->normalizedName($subjectName ?? ''))) && $mapping->class_level === $class
            && ($subject !== null || $this->mappingService->normalizedName($mapping->detected_subject ?? '') === $this->mappingService->normalizedName($subjectName ?? ''))
            && (($classification === 'topic' && $mapping->classification === 'topic' && $topic && $mapping->topic_id === $topic->id)
                || $this->mappingService->normalizedName($mapping->detected_topic) === $this->mappingService->normalizedName($topicName)));
        if ($decided) {
            return;
        }
        if ($existing && ($existing->status !== 'suggested' || $existing->reviewed_at !== null || $existing->merged_into_id !== null || $existing->evidence_status === 'needs_review')) {
            return;
        }
        $mapping = $existing ?? $document->curriculumMappings()->create([
            'identity_key' => $key, 'classification' => $classification, 'detected_parent_topic' => $classification === 'topic' ? null : $parent,
            'hierarchy_status' => $uncertain ? 'needs_review' : 'current',
            'subject_id' => $subject?->id, 'topic_id' => $topic?->id, 'class_level' => $class,
            'detected_subject' => $subjectName, 'detected_class' => $class, 'detected_topic' => $topicName,
            'section_heading' => is_string($suggestion['section_heading'] ?? null) ? mb_substr($suggestion['section_heading'], 0, 255) : null,
            'chunk_version' => $document->active_chunk_version, 'status' => 'suggested', 'provenance' => 'ai',
        ]);
        foreach ($evidence as $item) {
            $mapping->evidence()->firstOrCreate(['knowledge_document_chunk_id' => $item['knowledge_document_chunk_id'], 'excerpt' => $item['excerpt']], $item);
        }
    }

    private function ensureEligible(KnowledgeDocument $document, int $version): void
    {
        if ($document->active_chunk_version !== $version || $document->processing_status !== KnowledgeDocument::PROCESSING_COMPLETED
            || $document->approval_status !== KnowledgeDocument::APPROVAL_APPROVED) {
            throw new RuntimeException('Topic identification requires the current version of an approved, processed document.');
        }
    }
}
