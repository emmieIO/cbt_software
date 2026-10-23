<?php

namespace App\Models;

use Database\Factories\KnowledgeDocumentChunkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $knowledge_document_id
 * @property int $chunk_version
 * @property int $sequence
 * @property string $content
 * @property string|null $content_hash
 * @property int|null $estimated_token_count
 * @property array<string, mixed>|null $metadata
 * @property array<int, float>|null $embedding
 * @property string|null $embedding_provider
 * @property string|null $embedding_model
 * @property int|null $embedding_dimensions
 * @property string|null $embedded_content_hash
 * @property KnowledgeDocument|null $document
 */
class KnowledgeDocumentChunk extends Model
{
    /** @use HasFactory<KnowledgeDocumentChunkFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'knowledge_document_id',
        'chunk_version',
        'sequence',
        'content',
        'content_hash',
        'estimated_token_count',
        'metadata',
        'embedding',
        'embedding_provider',
        'embedding_model',
        'embedding_dimensions',
        'embedded_content_hash',
        'embedded_at',
    ];

    /** @return BelongsTo<KnowledgeDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'knowledge_document_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'chunk_version' => 'integer',
            'estimated_token_count' => 'integer',
            'metadata' => 'array',
            'embedding' => 'array',
            'embedding_dimensions' => 'integer',
            'embedded_at' => 'datetime',
        ];
    }
}
