<?php

namespace App\Services;

use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumStructureService
{
    public function __construct(private readonly CurriculumMappingService $mappings) {}

    public function reviewToken(KnowledgeDocument $document): string
    {
        return hash('sha256', $document->curriculumMappings()->with(['evidence' => fn ($query) => $query->orderBy('id')])->orderBy('id')->get()
            ->map(fn ($mapping): array => [...$mapping->getAttributes(), 'evidence' => $mapping->evidence->toArray()])->toJson());
    }

    /** @param array<string, mixed> $data */
    public function confirm(KnowledgeDocument $document, array $data, User $reviewer): void
    {
        abort_unless($reviewer->canReviewCurriculum(), 403);
        DB::transaction(function () use ($document, $data, $reviewer): void {
            $document = KnowledgeDocument::query()->lockForUpdate()->findOrFail($document->id);
            if (! hash_equals($this->reviewToken($document), $data['review_token'])) {
                throw ValidationException::withMessages(['structure' => 'The curriculum was reviewed or updated elsewhere. Refresh before confirming.']);
            }
            if ($document->active_chunk_version !== $data['chunk_version'] || $document->approval_status !== KnowledgeDocument::APPROVAL_APPROVED
                || $document->processing_status !== KnowledgeDocument::PROCESSING_COMPLETED || $document->embedding_status !== KnowledgeDocument::EMBEDDING_COMPLETED
                || $document->embedding_provider !== config('knowledge.embeddings.provider')
                || $document->embedding_model !== config('knowledge.embeddings.model')
                || $document->embedding_dimensions !== (int) config('knowledge.embeddings.dimensions')
                || in_array($document->topic_detection_status, ['queued', 'processing'], true)) {
                throw ValidationException::withMessages(['structure' => 'The document changed or is still processing. Refresh and review the current structure.']);
            }
            $ids = collect([$data['topic'], ...$data['children']])->flatMap(fn (array $row): array => $row['mapping_ids'])->all();
            if (count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['structure' => 'A suggestion cannot appear in more than one place.']);
            }
            $names = collect($data['children'])->map(fn (array $row): string => $row['classification'].'|'.$this->mappings->normalizedName($row['name']));
            if ($names->unique()->count() !== $names->count()) {
                throw ValidationException::withMessages(['structure' => 'Combine duplicate subtopics before confirming.']);
            }
            $root = $this->confirmRow($document, [...$data['topic'], 'classification' => 'topic'], $data, $reviewer);
            foreach ($data['children'] as $index => $child) {
                try {
                    $this->confirmRow($document, $child, $data, $reviewer, $root->topic);
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['children.'.$index.'.name' => implode(' ', $exception->validator->errors()->all())]);
                }
            }
        });
    }

    /** @param array<string, mixed> $row @param array<string, mixed> $data */
    private function confirmRow(KnowledgeDocument $document, array $row, array $data, User $reviewer, ?Topic $parent = null): CurriculumTopicMapping
    {
        $name = trim($row['name']);
        $classification = $row['classification'];
        $subject = Subject::query()->findOrFail($data['subject_id']);
        $rejected = $document->curriculumMappings()->where('status', 'rejected')->get()->contains(fn ($item): bool => ($item->class_level ?? $item->detected_class) === $data['class_level']
            && ($item->subject_id === $subject->id || ($item->subject_id === null && $this->mappings->normalizedName($item->detected_subject ?? '') === $this->mappings->normalizedName($subject->name)))
            && $this->mappings->normalizedName($item->detected_topic) === $this->mappings->normalizedName($name));
        if ($rejected) {
            throw ValidationException::withMessages(['structure' => 'A rejected item cannot be included. Review the rejection separately before confirming it.']);
        }
        $topic = $parent ?? $this->mappings->matchTopic($name, $data['subject_id'], $data['class_level']);
        $selected = $document->curriculumMappings()->whereIn('id', $row['mapping_ids'])->lockForUpdate()->get();
        if ($selected->count() !== count($row['mapping_ids']) || $selected->contains(fn ($item): bool => $item->status === 'rejected' || $item->merged_into_id !== null || $item->chunk_version !== $document->active_chunk_version)) {
            throw ValidationException::withMessages(['structure' => 'A selected suggestion is rejected, stale, or belongs to another document. Refresh the review.']);
        }
        $subtopic = $classification === 'subtopic' ? $topic?->subtopics()->where('normalized_name', $this->mappings->normalizedName($name))->first() : null;
        $canonical = $topic ? $document->curriculumMappings()->whereNull('merged_into_id')->where('topic_id', $topic->id)
            ->where('classification', $classification)->where('subtopic_id', $subtopic?->id)
            ->when($classification === 'section' || ($classification === 'subtopic' && ! $subtopic), fn ($q) => $q->where('detected_topic', $name))->lockForUpdate()->first() : null;
        if ($canonical?->status === 'rejected') {
            throw ValidationException::withMessages(['structure' => 'This curriculum item was rejected. Review that decision separately before including it.']);
        }
        $keeper = $canonical ?? $selected->first();
        $evidence = $row['evidence'];
        if ($canonical && ! $selected->contains('id', $canonical->id)) {
            $evidence = [...$evidence, ...$canonical->evidence()->get()->map(fn ($item): array => ['chunk_id' => $item->knowledge_document_chunk_id ?? '', 'excerpt' => $item->excerpt])->all()];
        }
        $this->mappings->validatedEvidence($document, $evidence);
        foreach ($selected as $duplicate) {
            if ($duplicate->id === $keeper?->id) {
                continue;
            }
            if ($duplicate->status !== 'suggested' || $duplicate->reviewed_at !== null) {
                throw ValidationException::withMessages(['structure' => 'Reviewed duplicates must be resolved separately. No decisions were changed.']);
            }
            $before = $this->mappings->snapshot($duplicate);
            $duplicate->update(['merged_into_id' => $keeper->id]);
            $this->mappings->recordReview($duplicate, $reviewer, 'structure_merge', $before);
        }

        return $this->mappings->review($document, $keeper, [
            'action' => 'confirm', 'classification' => $classification,
            'subject_id' => $data['subject_id'], 'class_level' => $data['class_level'],
            'topic_id' => $topic?->id, 'new_topic_name' => $parent ? null : $name,
            'subtopic_id' => $subtopic?->id, 'new_subtopic_name' => $classification === 'subtopic' ? $name : null,
            'suggested_name' => $name, 'evidence' => $evidence,
        ], $reviewer);
    }
}
