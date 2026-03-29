<?php

namespace App\Models;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'branch',
        'school_id',
        'subject_id',
        'school_class_id',
        'academic_session_id',
        'created_by',
        'title',
        'description',
        'instructions',
        'duration',
        'start_time',
        'end_time',
        'type',
        'status',
        'settings',
    ];

    /**
     * Get the school this exam belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    protected function casts(): array
    {
        return [
            'type' => ExamType::class,
            'status' => ExamStatus::class,
            'settings' => 'array',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exam_user')->withTimestamps();
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->using(ExamQuestion::class)
            ->withPivot(['id', 'marks', 'order'])
            ->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function compositions(): HasMany
    {
        return $this->hasMany(ExamComposition::class);
    }
}
