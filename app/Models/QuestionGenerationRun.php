<?php

namespace App\Models;

use Database\Factories\QuestionGenerationRunFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionGenerationRun extends Model
{
    /** @use HasFactory<QuestionGenerationRunFactory> */
    use HasFactory, HasUlids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RETRIEVING = 'retrieving';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const TERMINAL_STATUSES = [self::STATUS_COMPLETED, self::STATUS_PARTIAL, self::STATUS_FAILED];

    protected $fillable = [
        'subtopic_id', 'subtopic_name',
        'requested_by', 'subject_id', 'topic_id', 'level', 'class_level',
        'requested_count', 'type_distribution', 'difficulty', 'additional_instructions',
        'status', 'provider', 'model', 'retrieval_query', 'source_references',
        'validation_errors', 'warnings', 'error', 'started_at', 'completed_at',
        'import_status', 'last_import_summary', 'generation_attempt', 'queue_job_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
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

    /** @return BelongsTo<Subtopic, $this> */
    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    /** @return HasMany<GeneratedQuestionDraft, $this> */
    public function drafts(): HasMany
    {
        return $this->hasMany(GeneratedQuestionDraft::class)->orderBy('sequence');
    }

    protected function casts(): array
    {
        return [
            'requested_count' => 'integer',
            'type_distribution' => 'array',
            'source_references' => 'array',
            'validation_errors' => 'array',
            'warnings' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_import_summary' => 'array',
            'generation_attempt' => 'integer',
        ];
    }
}
