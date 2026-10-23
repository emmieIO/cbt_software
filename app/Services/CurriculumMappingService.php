<?php

namespace App\Services;

use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeDocumentChunk;
use App\Models\Subject;
use App\Models\Subtopic;
use App\Models\Topic;
use App\Models\User;
use App\Support\AcademicLevels;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CurriculumMappingService
{
    public function __construct(private readonly TopicService $topicService) {}

    public function normalizedName(string $name): string
    {
        $name = str_replace(['factorisation', 'factorization'], 'factorization', mb_strtolower($name));
        $name = preg_replace('/(?<=\s)-(?=\s)|(?<=\pN)-(?=\pN)/u', '−', $name) ?? $name;
        $words = preg_split('/[^\pL\pN\p{Sm}^]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_map(fn (string $word): string => Str::singular($word), $words);

        return implode(' ', $words);
    }

    public function matchTopic(string $name, string $subjectId, string $classLevel): ?Topic
    {
        $normalized = $this->normalizedName($name);
        $topics = Topic::query()->where('subject_id', $subjectId)->where('class_level', $classLevel)->get();

        return $topics->first(fn (Topic $topic): bool => $this->normalizedName($topic->name) === $normalized);
    }

    /** @return Collection<int, CurriculumTopicMapping> */
    public function coverage(?string $subjectId = null, ?string $classLevel = null, ?string $topicId = null, ?string $subtopicId = null): Collection
    {
        return CurriculumTopicMapping::query()->eligible()
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($classLevel, fn ($q) => $q->where('class_level', $classLevel))
            ->when($topicId, fn ($q) => $q->where('topic_id', $topicId))
            ->when($subtopicId, fn ($q) => $q->where('subtopic_id', $subtopicId))
            ->with(['document:id,title,original_name', 'topic:id,name,subject_id,class_level', 'evidence.chunk:id,knowledge_document_id,chunk_version,content_hash,content'])
            ->get();
    }

    /** @return array<int, string> */
    public function chunkIds(string $subjectId, string $classLevel, string $topicId, ?string $subtopicId = null): array
    {
        return $this->coverage($subjectId, $classLevel, $topicId, $subtopicId)
            ->flatMap(fn (CurriculumTopicMapping $mapping) => $mapping->evidence
                ->filter(fn ($evidence): bool => $evidence->chunk !== null
                    && $evidence->chunk->chunk_version === $mapping->chunk_version
                    && $evidence->chunk->knowledge_document_id === $mapping->knowledge_document_id
                    && hash_equals($evidence->content_hash, $evidence->chunk->content_hash ?? hash('sha256', $evidence->chunk->content)))
                ->pluck('knowledge_document_chunk_id'))
            ->unique()->values()->all();
    }

    /** @param array<string, mixed> $data */
    public function review(KnowledgeDocument $document, ?CurriculumTopicMapping $mapping, array $data, User $reviewer): CurriculumTopicMapping
    {
        abort_unless($reviewer->canReviewCurriculum(), 403);

        return DB::transaction(function () use ($document, $mapping, $data, $reviewer): CurriculumTopicMapping {
            $document = KnowledgeDocument::query()->lockForUpdate()->findOrFail($document->id);
            $mapping = $mapping ? $document->curriculumMappings()->lockForUpdate()->findOrFail($mapping->id) : null;
            abort_if($mapping?->merged_into_id !== null, 409, 'Review the retained mapping instead.');
            $before = $mapping ? $this->snapshot($mapping) : [];
            if ($data['action'] === 'merge' && $mapping) {
                return app(CurriculumReconciliationService::class)->merge($document, $mapping, (string) $data['merge_target_id'], $reviewer);
            }
            if ($data['action'] === 'reject' && $mapping) {
                $mapping->update(['status' => 'rejected', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null]);
                $this->recordReview($mapping, $reviewer, 'reject', $before);

                return $mapping;
            }

            if ($document->approval_status !== KnowledgeDocument::APPROVAL_APPROVED || $document->processing_status !== KnowledgeDocument::PROCESSING_COMPLETED) {
                throw ValidationException::withMessages(['mapping' => 'Approve and finish processing the document before confirming coverage.']);
            }
            $subject = Subject::query()->lockForUpdate()->findOrFail($data['subject_id']);
            $classLevel = $data['class_level'];
            if (! AcademicLevels::classBelongsToLevel($classLevel, $subject->level)) {
                throw ValidationException::withMessages(['class_level' => 'The selected class does not belong to this subject level.']);
            }

            $topic = filled($data['topic_id'] ?? null) ? Topic::query()->findOrFail($data['topic_id']) : null;
            if (! $topic) {
                $name = trim($data['new_topic_name'] ?? '');
                if (! $reviewer->canCreateQuestions() || $name === '') {
                    throw ValidationException::withMessages(['topic_id' => 'Select an existing topic. Creating a topic requires question creation permission.']);
                }
                $topic = $this->matchTopic($name, $subject->id, $classLevel);
                if (! $topic) {
                    $topic = $this->topicService->create(['name' => $name, 'subject_id' => $subject->id, 'class_level' => $classLevel]);
                }
            }
            if ($topic->subject_id !== $subject->id || $topic->class_level !== $classLevel) {
                throw ValidationException::withMessages(['topic_id' => 'The topic must belong to the selected subject and class.']);
            }

            $evidence = $this->validatedEvidence($document, $data['evidence'] ?? []);
            if ($evidence === []) {
                throw ValidationException::withMessages(['evidence' => 'Select at least one current source passage.']);
            }
            $classification = $data['classification'] ?? 'topic';
            $subtopic = null;
            $name = trim($data['suggested_name'] ?? $mapping?->detected_topic ?? $topic->name);
            if ($classification === 'subtopic' || filled($data['subtopic_id'] ?? null)) {
                $subtopic = filled($data['subtopic_id'] ?? null) ? Subtopic::query()->findOrFail($data['subtopic_id']) : null;
                if (! $subtopic) {
                    $name = trim($data['new_subtopic_name'] ?? $name);
                    if (! $reviewer->canCreateQuestions() || $name === '') {
                        throw ValidationException::withMessages(['subtopic_id' => 'Select a subtopic or provide a name with question creation permission.']);
                    }
                    $subtopic = $topic->subtopics()->firstOrCreate(['normalized_name' => $this->normalizedName($name)], ['name' => $name]);
                }
                if ($subtopic->topic_id !== $topic->id) {
                    throw ValidationException::withMessages(['subtopic_id' => 'The subtopic must belong to the selected parent topic.']);
                }
                $name = $subtopic->name;
            } elseif ($classification === 'topic') {
                $name = $topic->name;
            }
            if ($classification === 'section' && $name === '') {
                throw ValidationException::withMessages(['suggested_name' => 'Provide the supporting section name.']);
            }
            $key = $this->identity($subject->id, $classLevel, $classification, $classification === 'topic' ? '' : $topic->name, $name);
            $duplicate = $document->curriculumMappings()->whereNull('merged_into_id')->where(fn ($q) => $q->where('identity_key', $key)
                ->orWhere(fn ($q) => $q->where('classification', $classification)->where('topic_id', $topic->id)
                    ->where('subtopic_id', $subtopic?->id)
                    ->when($classification === 'section', fn ($q) => $q->where('detected_topic', $name))))
                ->when($mapping, fn ($q) => $q->whereKeyNot($mapping->id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['topic_id' => 'This document already has a mapping for that topic. Review the existing mapping instead.']);
            }

            $mapping ??= $document->curriculumMappings()->make([
                'provenance' => 'manual', 'created_by' => $reviewer->id,
                'detected_topic' => $topic->name,
            ]);
            $mapping->fill([
                'subject_id' => $subject->id, 'class_level' => $classLevel, 'topic_id' => $topic->id,
                'identity_key' => $key, 'chunk_version' => $document->active_chunk_version,
                'classification' => $classification, 'subtopic_id' => $subtopic?->id,
                'detected_topic' => $classification === 'topic' && filled($data['suggested_name'] ?? null) ? trim($data['suggested_name']) : $name, 'detected_parent_topic' => $classification === 'topic' ? null : $topic->name,
                'hierarchy_status' => 'current',
                'status' => $data['action'] === 'save' ? 'suggested' : 'confirmed', 'evidence_status' => 'current', 'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null,
            ])->save();
            $mapping->evidence()->delete();
            $mapping->evidence()->createMany($evidence);
            $this->recordReview($mapping, $reviewer, $data['action'], $before);

            return $mapping;
        });
    }

    public function identity(string $subject, ?string $class, string $classification, string $parent, string $name): string
    {
        return hash('sha256', implode('|', [$subject, $class ?? '', $classification, $this->normalizedName($parent), $this->normalizedName($name)]));
    }

    /** @return array<string, mixed> */
    public function snapshot(CurriculumTopicMapping $mapping): array
    {
        return [...$mapping->getAttributes(), 'evidence' => $mapping->evidence()->get()->toArray()];
    }

    /** @param array<string, mixed> $before */
    public function recordReview(CurriculumTopicMapping $mapping, ?User $reviewer, string $action, array $before): void
    {
        $mapping->reviews()->create(['reviewed_by' => $reviewer?->id, 'action' => $action,
            'before_snapshot' => $before, 'after_snapshot' => $this->snapshot($mapping)]);
    }

    /**
     * @param  array<int, array{chunk_id: string, excerpt?: string}>  $items
     * @return array<int, array<string, mixed>>
     */
    public function validatedEvidence(KnowledgeDocument $document, array $items): array
    {
        $chunks = $document->chunks()->where('chunk_version', $document->active_chunk_version)
            ->whereIn('id', array_column($items, 'chunk_id'))->get()->keyBy('id');
        $result = [];
        foreach ($items as $item) {
            $chunk = $chunks->get($item['chunk_id']);
            if (! $chunk) {
                throw ValidationException::withMessages(['evidence' => 'A source passage is not part of the active document.']);
            }
            $excerpt = trim($item['excerpt'] ?? mb_substr($chunk->content, 0, 500));
            if (mb_strlen($excerpt) < 12 || mb_strlen($excerpt) > 2000 || ! str_contains($this->normalizeText($chunk->content), $this->normalizeText($excerpt))) {
                throw ValidationException::withMessages(['evidence' => 'The quoted passage must appear in the selected source chunk.']);
            }
            $result[$chunk->id.'|'.hash('sha256', $excerpt)] = $this->evidenceRecord($chunk, $excerpt);
        }

        return array_values($result);
    }

    /** Rebind evidence before obsolete chunks are deleted, in the activation transaction. */
    public function revalidate(KnowledgeDocument $document): void
    {
        $chunks = $document->chunks()->where('chunk_version', $document->active_chunk_version)->get();
        foreach ($document->curriculumMappings()->with('evidence')->get() as $mapping) {
            $before = $this->snapshot($mapping);
            $replacement = [];
            foreach ($mapping->evidence as $evidence) {
                $matches = $chunks->filter(fn (KnowledgeDocumentChunk $chunk): bool => str_contains($this->normalizeText($chunk->content), $this->normalizeText($evidence->excerpt)));
                $exact = $matches->filter(fn ($chunk): bool => $chunk->content_hash === $evidence->content_hash);
                $chunk = $exact->count() === 1 ? $exact->first() : ($matches->count() === 1 ? $matches->first() : null);
                if (! $chunk) {
                    $replacement = [];
                    break;
                }
                $replacement[$chunk->id.'|'.hash('sha256', $evidence->excerpt)] = $this->evidenceRecord($chunk, $evidence->excerpt);
            }
            if ($replacement === []) {
                $mapping->update(['evidence_status' => 'needs_review']);
                $this->recordReview($mapping, null, 'evidence_needs_review', $before);

                continue;
            }
            $mapping->evidence()->delete();
            $mapping->evidence()->createMany(array_values($replacement));
            $mapping->update(['chunk_version' => $document->active_chunk_version, 'evidence_status' => 'current']);
            $this->recordReview($mapping, null, 'evidence_revalidated', $before);
        }
    }

    public function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** @return array<string, mixed> */
    private function evidenceRecord(KnowledgeDocumentChunk $chunk, string $excerpt): array
    {
        return [
            'knowledge_document_chunk_id' => $chunk->id,
            'content_hash' => $chunk->content_hash ?? hash('sha256', $chunk->content),
            'excerpt' => $excerpt,
            'page_start' => $chunk->metadata['page_start'] ?? $chunk->metadata['page'] ?? null,
            'page_end' => $chunk->metadata['page_end'] ?? $chunk->metadata['page'] ?? null,
        ];
    }
}
