<?php

namespace App\Models;

use Database\Factories\GeneratedQuestionDraftFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratedQuestionDraft extends Model
{
    protected $attributes = ['review_status' => 'draft', 'revision' => 1, 'answers_verified' => false];

    /** @use HasFactory<GeneratedQuestionDraftFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'question_generation_run_id', 'sequence', 'type', 'difficulty', 'content',
        'explanation', 'expected_answer', 'options', 'marking_scheme', 'source_references',
        'validation_errors', 'warnings', 'is_valid', 'edited_at',
        'review_status', 'revision', 'edited_by', 'reviewed_by', 'reviewed_at', 'approved_hash', 'answers_verified', 'imported_by', 'imported_at', 'question_id', 'import_errors',
    ];

    /** @return BelongsTo<QuestionGenerationRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(QuestionGenerationRun::class, 'question_generation_run_id');
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'options' => 'array',
            'marking_scheme' => 'array',
            'source_references' => 'array',
            'validation_errors' => 'array',
            'warnings' => 'array',
            'is_valid' => 'boolean',
            'edited_at' => 'datetime',
            'reviewed_at' => 'datetime', 'imported_at' => 'datetime', 'answers_verified' => 'boolean', 'revision' => 'integer', 'import_errors' => 'array',
        ];
    }

    /** @return HasMany<GeneratedDraftReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(GeneratedDraftReview::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
