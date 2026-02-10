<?php

namespace App\Models;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'topic_id',
        'school_class_id',
        'content',
        'explanation',
        'type',
        'difficulty',
        'last_used_at',
        'created_by',
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
        ];
    }

    /**
     * Scope a query to filter questions.
     */
    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where('content', 'like', '%'.$search.'%');
        })->when($filters['subject_id'] ?? null, function ($query, $subjectId) {
            $query->whereHas('topic', function ($query) use ($subjectId) {
                $query->where('subject_id', $subjectId);
            });
        })->when($filters['school_class_id'] ?? null, function ($query, $classId) {
            $query->where('school_class_id', $classId);
        })->when($filters['difficulty'] ?? null, function ($query, $difficulty) {
            $query->where('difficulty', $difficulty);
        });
    }
}
