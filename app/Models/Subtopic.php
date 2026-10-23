<?php

namespace App\Models;

use Database\Factories\SubtopicFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subtopic extends Model
{
    /** @use HasFactory<SubtopicFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['topic_id', 'name', 'normalized_name'];

    /** @return BelongsTo<Topic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /** @return HasMany<CurriculumTopicMapping, $this> */
    public function curriculumMappings(): HasMany
    {
        return $this->hasMany(CurriculumTopicMapping::class);
    }
}
