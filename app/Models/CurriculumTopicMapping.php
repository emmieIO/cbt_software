<?php

namespace App\Models;

use Database\Factories\CurriculumTopicMappingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumTopicMapping extends Model
{
    /** @use HasFactory<CurriculumTopicMappingFactory> */
    use HasFactory, HasUlids;

    protected $attributes = ['status' => 'suggested', 'evidence_status' => 'current', 'provenance' => 'ai', 'classification' => 'topic', 'hierarchy_status' => 'current'];

    protected $fillable = [
        'classification', 'detected_parent_topic', 'subtopic_id', 'hierarchy_status', 'merged_into_id',
        'knowledge_document_id', 'subject_id', 'topic_id', 'class_level', 'detected_subject',
        'detected_class', 'detected_topic', 'section_heading', 'identity_key', 'chunk_version',
        'status', 'evidence_status', 'provenance', 'created_by', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    /** @return BelongsTo<KnowledgeDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'knowledge_document_id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<Topic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<CurriculumTopicEvidence, $this> */
    public function evidence(): HasMany
    {
        return $this->hasMany(CurriculumTopicEvidence::class);
    }

    /** @return BelongsTo<Subtopic, $this> */
    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    /** @return HasMany<CurriculumMappingReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(CurriculumMappingReview::class)->orderBy('created_at');
    }

    public function scopeEligible(Builder $query): void
    {
        $this->scopeUsableEvidence($query);
        $query->where('hierarchy_status', 'current')
            ->where(function (Builder $q): void {
                $q->where(fn ($q) => $q->where('classification', 'topic')->whereNull('subtopic_id'))
                    ->orWhere(fn ($q) => $q->whereIn('classification', ['subtopic', 'section'])
                        ->whereIn('topic_id', self::query()->usableEvidence()->where('hierarchy_status', 'current')
                            ->where('classification', 'topic')->whereNull('subtopic_id')->select('topic_id'))
                        ->where(fn ($q) => $q->where(fn ($q) => $q->where('classification', 'section')->whereNull('subtopic_id'))
                            ->orWhere(fn ($q) => $q->whereHas('subtopic', fn ($q) => $q->whereColumn('subtopics.topic_id', 'curriculum_topic_mappings.topic_id'))
                                ->where(fn ($q) => $q->where('classification', 'subtopic')->orWhereIn('subtopic_id', self::query()->usableEvidence()
                                    ->where('hierarchy_status', 'current')->where('classification', 'subtopic')->whereNotNull('subtopic_id')->select('subtopic_id'))))));
            });
    }

    public function scopeUsableEvidence(Builder $query): void
    {
        $query->whereNull('merged_into_id')->where('status', 'confirmed')->where('evidence_status', 'current')
            ->whereNotNull('subject_id')->whereNotNull('topic_id')->whereNotNull('class_level')
            ->whereHas('topic', fn ($q) => $q
                ->whereColumn('topics.subject_id', 'curriculum_topic_mappings.subject_id')
                ->whereColumn('topics.class_level', 'curriculum_topic_mappings.class_level'))
            ->whereHas('document', fn ($q) => $q
                ->where('approval_status', KnowledgeDocument::APPROVAL_APPROVED)
                ->where('processing_status', KnowledgeDocument::PROCESSING_COMPLETED)
                ->where('embedding_status', KnowledgeDocument::EMBEDDING_COMPLETED)
                ->where('embedding_provider', config('knowledge.embeddings.provider'))
                ->where('embedding_model', config('knowledge.embeddings.model'))
                ->where('embedding_dimensions', config('knowledge.embeddings.dimensions'))
                ->whereColumn('knowledge_documents.active_chunk_version', 'curriculum_topic_mappings.chunk_version'))
            ->whereHas('evidence.chunk', fn ($q) => $q
                ->whereColumn('knowledge_document_chunks.chunk_version', 'curriculum_topic_mappings.chunk_version')
                ->whereColumn('knowledge_document_chunks.knowledge_document_id', 'curriculum_topic_mappings.knowledge_document_id'))
            ->whereDoesntHave('evidence', fn ($q) => $q->whereNull('knowledge_document_chunk_id'));
        $query->whereDoesntHave('evidence', fn ($q) => $q->whereDoesntHave('chunk', fn ($q) => $q
            ->whereColumn('knowledge_document_chunks.content_hash', 'curriculum_topic_evidence.content_hash')
            ->whereColumn('knowledge_document_chunks.chunk_version', 'curriculum_topic_mappings.chunk_version')
            ->whereColumn('knowledge_document_chunks.knowledge_document_id', 'curriculum_topic_mappings.knowledge_document_id')));
    }

    protected function casts(): array
    {
        return ['chunk_version' => 'integer', 'reviewed_at' => 'datetime'];
    }
}
