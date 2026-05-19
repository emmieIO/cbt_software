<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exam extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'title',
        'subject_name',
        'level',
        'instructions',
        'mcq_count',
        'theory_count',
        'total_marks',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_question')
            ->withPivot('section', 'sort_order')
            ->orderBy('exam_question.sort_order');
    }

    public function mcqs(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_question')
            ->wherePivot('section', 'mcq')
            ->withPivot('sort_order')
            ->orderBy('exam_question.sort_order');
    }

    public function theoryQuestions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_question')
            ->wherePivot('section', 'theory')
            ->withPivot('sort_order')
            ->orderBy('exam_question.sort_order');
    }
}
