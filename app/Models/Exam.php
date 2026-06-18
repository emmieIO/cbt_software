<?php

namespace App\Models;

use App\Enums\QuestionLevel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $title
 * @property string $subject_name
 * @property string|QuestionLevel $level
 * @property string|null $class_level
 * @property string|null $instructions
 * @property int $mcq_count
 * @property int $theory_count
 * @property int $total_marks
 * @property Carbon $created_at
 * @property User|null $creator
 * @property Collection<int, Question> $questions
 * @property Collection<int, Question> $mcqs
 * @property Collection<int, Question> $theoryQuestions
 */
class Exam extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'title',
        'subject_name',
        'level',
        'class_level',
        'instructions',
        'mcq_count',
        'theory_count',
        'total_marks',
        'created_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_question')
            ->withPivot('section', 'sort_order')
            ->orderBy('exam_question.sort_order');
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function mcqs(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_question')
            ->wherePivot('section', 'mcq')
            ->withPivot('sort_order')
            ->orderBy('exam_question.sort_order');
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function theoryQuestions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_question')
            ->wherePivot('section', 'theory')
            ->withPivot('sort_order')
            ->orderBy('exam_question.sort_order');
    }
}
