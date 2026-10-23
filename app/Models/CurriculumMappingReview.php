<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumMappingReview extends Model
{
    use HasUlids;

    protected $fillable = ['reviewed_by', 'action', 'before_snapshot', 'after_snapshot'];

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return ['before_snapshot' => 'array', 'after_snapshot' => 'array'];
    }
}
