<?php

namespace App\Services;

use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Models\Subject;
use App\Models\User;
use App\Support\AcademicLevels;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumReconciliationService
{
    public function __construct(private readonly CurriculumMappingService $mappings) {}

    /** @return array<int, array{mapping: string, result: string, detail: string}> */
    public function reconcile(KnowledgeDocument $document, User $reviewer, bool $apply = false): array
    {
        abort_unless($reviewer->canReviewCurriculum(), 403);

        return DB::transaction(function () use ($document, $reviewer, $apply): array {
            $document = KnowledgeDocument::query()->lockForUpdate()->findOrFail($document->id);
            $groups = [];
            $report = [];
            $all = $document->curriculumMappings()->whereNull('merged_into_id')->with(['evidence', 'topic', 'subject'])->oldest()->orderBy('id')->lockForUpdate()->get();
            $title = $this->mappings->normalizedName($document->title);
            $roots = $all->filter(fn ($item): bool => $item->classification === 'topic' && str_contains($title, $this->mappings->normalizedName($item->detected_topic)));
            foreach ($all as $mapping) {
                $result = 'retained';
                $detail = 'Human decision preserved';
                if ($mapping->status === 'suggested' && $mapping->reviewed_at === null) {
                    $context = $this->context($mapping).'|'.$this->mappings->normalizedName($mapping->detected_topic);
                    $ambiguous = $mapping->hierarchy_status === 'needs_review'
                        || ($mapping->classification === 'topic' && $roots->isNotEmpty() && ! $roots->contains('id', $mapping->id))
                        || ($mapping->classification === 'topic' && $mapping->topic_id && $mapping->topic
                            && $this->mappings->normalizedName($mapping->detected_topic) !== $this->mappings->normalizedName($mapping->topic->name))
                        || ($mapping->classification !== 'topic' && blank($mapping->detected_parent_topic) && ! $mapping->topic_id)
                        || preg_match('/^(common misconceptions?|learning objectives?|worked examples?|guided practice|source notes?|application\s*:)/i', $mapping->detected_topic);
                    $validEvidence = true;
                    try {
                        $valid = $this->mappings->validatedEvidence($document, $mapping->evidence->map(fn ($item): array => ['chunk_id' => $item->knowledge_document_chunk_id ?? '', 'excerpt' => $item->excerpt])->all());
                        $ambiguous = $ambiguous || $valid === [];
                        $validEvidence = $valid !== [];
                    } catch (ValidationException) {
                        $ambiguous = true;
                        $validEvidence = false;
                    }
                    $current = $validEvidence && $mapping->evidence_status === 'current' && $mapping->chunk_version === $document->active_chunk_version;
                    if ($current && isset($groups[$context])) {
                        $result = 'merged';
                        $detail = $groups[$context];
                        if ($apply) {
                            $this->merge($document, $mapping, $detail, $reviewer);
                        }
                    } elseif ($ambiguous || ! $current) {
                        $result = 'flagged';
                        $detail = 'Hierarchy or evidence needs manual review';
                        if ($apply && $mapping->hierarchy_status !== 'needs_review') {
                            $before = $this->mappings->snapshot($mapping);
                            $mapping->update(['hierarchy_status' => 'needs_review']);
                            $this->mappings->recordReview($mapping, $reviewer, 'reconcile_flag', $before);
                        }
                    } else {
                        $detail = 'Distinct pending suggestion';
                    }
                    if ($current && $result !== 'merged') {
                        $groups[$context] = $mapping->id;
                    }
                }
                $report[] = ['mapping' => $mapping->id, 'result' => $result, 'detail' => $detail];
            }

            return $report;
        });
    }

    public function merge(KnowledgeDocument $document, CurriculumTopicMapping $source, string $targetId, User $reviewer): CurriculumTopicMapping
    {
        abort_unless($reviewer->canReviewCurriculum(), 403);

        return DB::transaction(function () use ($document, $source, $targetId, $reviewer): CurriculumTopicMapping {
            $document = KnowledgeDocument::query()->lockForUpdate()->findOrFail($document->id);
            $source = $document->curriculumMappings()->lockForUpdate()->findOrFail($source->id);
            $target = $document->curriculumMappings()->lockForUpdate()->findOrFail($targetId);
            foreach ([$source, $target] as $mapping) {
                if ($mapping->status !== 'suggested' || $mapping->reviewed_at !== null || $mapping->merged_into_id !== null
                    || $mapping->chunk_version !== $document->active_chunk_version || $mapping->evidence_status !== 'current') {
                    throw ValidationException::withMessages(['merge_target_id' => 'Only current, unreviewed pending suggestions can be merged.']);
                }
                $valid = $this->mappings->validatedEvidence($document, $mapping->evidence()->get()->map(fn ($item): array => ['chunk_id' => $item->knowledge_document_chunk_id ?? '', 'excerpt' => $item->excerpt])->all());
                if ($valid === []) {
                    throw ValidationException::withMessages(['merge_target_id' => 'Both suggestions require valid current evidence.']);
                }
            }
            if ($source->id === $target->id || $this->context($source) !== $this->context($target)) {
                throw ValidationException::withMessages(['merge_target_id' => 'Select another suggestion in the same subject, class, classification and parent topic.']);
            }
            $evidence = $this->mappings->validatedEvidence($document, $source->evidence()->get()->map(fn ($item): array => ['chunk_id' => $item->knowledge_document_chunk_id, 'excerpt' => $item->excerpt])->all());
            $beforeSource = $this->mappings->snapshot($source);
            $beforeTarget = $this->mappings->snapshot($target);
            foreach ($evidence as $item) {
                $target->evidence()->firstOrCreate(['knowledge_document_chunk_id' => $item['knowledge_document_chunk_id'], 'excerpt' => $item['excerpt']], $item);
            }
            $source->update(['merged_into_id' => $target->id]);
            $this->mappings->recordReview($source, $reviewer, 'merge_source', $beforeSource);
            $this->mappings->recordReview($target, $reviewer, 'merge_target', $beforeTarget);

            return $target;
        });
    }

    private function context(CurriculumTopicMapping $mapping): string
    {
        $class = $mapping->class_level ?? $mapping->detected_class ?? '';
        $subjectId = $mapping->subject_id;
        if (! $subjectId && $class !== '') {
            $matches = Subject::query()->get()->filter(fn (Subject $subject): bool => AcademicLevels::classBelongsToLevel($class, $subject->level)
                && $this->mappings->normalizedName($subject->name) === $this->mappings->normalizedName($mapping->detected_subject ?? ''));
            $subjectId = $matches->count() === 1 ? $matches->first()->id : null;
        }
        $parent = $mapping->classification === 'topic' ? '' : $this->mappings->normalizedName($mapping->topic?->name ?? $mapping->detected_parent_topic ?? '');

        return implode('|', [$subjectId ?? $this->mappings->normalizedName($mapping->detected_subject ?? ''), $class,
            $mapping->classification, $parent, $mapping->subtopic_id ?? '']);
    }
}
