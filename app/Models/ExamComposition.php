<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamComposition extends Model
{
    use HasUlids;

    protected $fillable = [
        'exam_id',
        'subject_id',
        'topic_id',
        'source_class_id',
        'question_count',
        'marks_per_question',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function sourceClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'source_class_id');
    }
}
