<?php

namespace App\Models;

use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $subject_id
 * @property string|null $class_level
 * @property string $name
 * @property Subject|null $subject
 */
class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'subject_id',
        'class_level',
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the subject that owns the topic.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the questions for the topic.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
