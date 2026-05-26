<?php

namespace App\Models;

use App\Enums\QuestionLevel;
use App\Enums\QuestionType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string|null $created_by
 * @property string $content
 * @property string|null $image_path
 * @property string|null $image_url
 * @property QuestionType|string $type
 * @property QuestionLevel|string|null $level
 * @property array<int, array{point: string, weight: int}>|null $marking_scheme
 * @property int $used_count
 * @property Carbon|null $last_used_at
 * @property Topic|null $topic
 * @property User|null $creator
 * @property Collection<int, Option> $options
 */
class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $appends = ['image_url'];

    protected $fillable = [
        'topic_id',
        'content',
        'image_path',
        'explanation',
        'type',
        'level',
        'marking_scheme',
        'used_count',
        'last_used_at',
        'created_by',
    ];

    /**
     * Get the topic that owns the question.
     *
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the options for the question.
     *
     * @return HasMany<Option, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    /**
     * Get the user who created the question.
     *
     * @return BelongsTo<User, $this>
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
            'level' => QuestionLevel::class,
            'marking_scheme' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->resolveImageUrl());
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
        })->when($filters['level'] ?? null, function ($query, $level) {
            $query->where('level', $level);
        });
    }

    /**
     * Scope to flag frequently used questions above a threshold.
     */
    public function scopeFrequentlyUsed($query, int $threshold = 3): void
    {
        $query->where('used_count', '>=', $threshold);
    }

    /**
     * Mark the question as used (increment count and update timestamp).
     */
    public function markAsUsed(): void
    {
        $this->increment('used_count');
        $this->update(['last_used_at' => now()]);
    }

    public function imagePdfSource(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if ($this->isExternalImagePath()) {
            return $this->image_path;
        }

        return Storage::disk('public')->path($this->image_path);
    }

    public function printableContent(): string
    {
        return trim((string) preg_replace(
            '/^[^:]+:\s*Question\s+\d+\s+on\s+[^.?!]+[.?!]\s*/i',
            '',
            $this->content,
        ));
    }

    private function resolveImageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if ($this->isExternalImagePath()) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    private function isExternalImagePath(): bool
    {
        return str_starts_with($this->image_path ?? '', 'http://')
            || str_starts_with($this->image_path ?? '', 'https://');
    }
}
