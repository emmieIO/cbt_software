<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\KnowledgeDocumentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $title
 * @property string $document_type
 * @property string|null $description
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property string $extension
 * @property int $size_bytes
 * @property string $checksum
 * @property string $processing_status
 * @property string $approval_status
 * @property string $embedding_status
 * @property int $active_chunk_version
 * @property int|null $pending_chunk_version
 * @property string|null $reprocessing_status
 * @property string|null $reprocessing_error
 * @property string|null $processing_error
 * @property string|null $embedding_provider
 * @property string|null $embedding_model
 * @property int|null $embedding_dimensions
 * @property string|null $embedding_error
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $approved_at
 * @property Carbon|null $embedded_at
 * @property User|null $uploader
 * @property User|null $approver
 * @property Collection<int, KnowledgeDocumentChunk> $chunks
 */
class KnowledgeDocument extends Model
{
    /** @use HasFactory<KnowledgeDocumentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    public const TYPE_CURRICULUM = 'curriculum';

    public const TYPE_LESSON_NOTE = 'lesson_note';

    public const TYPE_TEXTBOOK = 'textbook';

    public const TYPE_OTHER = 'other';

    public const DOCUMENT_TYPES = [
        self::TYPE_CURRICULUM,
        self::TYPE_LESSON_NOTE,
        self::TYPE_TEXTBOOK,
        self::TYPE_OTHER,
    ];

    public const PROCESSING_PENDING = 'pending';

    public const PROCESSING_PROCESSING = 'processing';

    public const PROCESSING_COMPLETED = 'completed';

    public const PROCESSING_FAILED = 'failed';

    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public const EMBEDDING_PENDING = 'pending';

    public const EMBEDDING_PROCESSING = 'processing';

    public const EMBEDDING_COMPLETED = 'completed';

    public const EMBEDDING_FAILED = 'failed';

    public const REPROCESSING_PROCESSING = 'processing';

    public const REPROCESSING_EMBEDDING = 'embedding';

    public const REPROCESSING_FAILED = 'failed';

    protected $attributes = [
        'topic_detection_status' => 'pending',
        'topic_detection_cursor' => 0,
        'disk' => 'local',
        'processing_status' => self::PROCESSING_PENDING,
        'approval_status' => self::APPROVAL_PENDING,
        'embedding_status' => self::EMBEDDING_PENDING,
        'active_chunk_version' => 1,
    ];

    protected $fillable = [
        'topic_detection_status', 'topic_detection_error', 'topic_detection_version', 'topic_detection_cursor',
        'title',
        'document_type',
        'description',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'extension',
        'size_bytes',
        'checksum',
        'processing_status',
        'approval_status',
        'processing_error',
        'embedding_status',
        'active_chunk_version',
        'pending_chunk_version',
        'reprocessing_status',
        'reprocessing_error',
        'embedding_provider',
        'embedding_model',
        'embedding_dimensions',
        'embedding_error',
        'embedded_at',
        'metadata',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<KnowledgeDocumentChunk, $this> */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeDocumentChunk::class)->orderBy('sequence');
    }

    /** @return HasMany<CurriculumTopicMapping, $this> */
    public function curriculumMappings(): HasMany
    {
        return $this->hasMany(CurriculumTopicMapping::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'topic_detection_version' => 'integer',
            'topic_detection_cursor' => 'integer',
            'approved_at' => 'datetime',
            'embedded_at' => 'datetime',
            'embedding_dimensions' => 'integer',
            'active_chunk_version' => 'integer',
            'pending_chunk_version' => 'integer',
            'size_bytes' => 'integer',
        ];
    }
}
