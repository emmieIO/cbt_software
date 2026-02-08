<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ExamQuestion extends Pivot
{
    use HasUlids;

    protected $table = 'exam_questions';

    public $incrementing = false;

    protected $keyType = 'string';
}
