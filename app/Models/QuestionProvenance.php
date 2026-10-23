<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class QuestionProvenance extends Model
{
    use HasUlids;

    protected $fillable = ['question_id', 'question_generation_run_id', 'generated_question_draft_id', 'knowledge_document_id', 'document_identity', 'document_title', 'original_name', 'document_checksum', 'chunk_identity', 'chunk_version', 'content_hash', 'page_start', 'page_end', 'curriculum_topic_mapping_id', 'curriculum_snapshot'];

    protected function casts(): array
    {
        return ['curriculum_snapshot' => 'array', 'chunk_version' => 'integer'];
    }
}
