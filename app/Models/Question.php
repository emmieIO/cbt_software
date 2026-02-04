<?php

namespace App\Models;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'school_class_id',
        'content',
        'explanation',
        'type',
        'difficulty',
        'version',
        'parent_id',
        'last_used_at',
        'created_by',
        'is_active',
    ];

    /**
     * Get the topic that owns the question.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the class that owns the question.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Get the options for the question.
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    /**
     * Get the user who created the question.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent question (for versioning).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'parent_id');
    }

    /**
     * Get the child versions of the question.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Question::class, 'parent_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'difficulty' => QuestionDifficulty::class,
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
