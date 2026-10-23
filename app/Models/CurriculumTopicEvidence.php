<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumTopicEvidence extends Model
{
    use HasUlids;

    protected $table = 'curriculum_topic_evidence';

    protected $fillable = ['knowledge_document_chunk_id', 'content_hash', 'excerpt', 'page_start', 'page_end'];

    /** @return BelongsTo<CurriculumTopicMapping, $this> */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(CurriculumTopicMapping::class, 'curriculum_topic_mapping_id');
    }

    /** @return BelongsTo<KnowledgeDocumentChunk, $this> */
    public function chunk(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocumentChunk::class, 'knowledge_document_chunk_id');
    }
}
