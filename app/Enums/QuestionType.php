<?php

namespace App\Enums;

enum QuestionType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case SHORT_ANSWER = 'short_answer';
    case TRUE_FALSE = 'true_false';
    case THEORY = 'theory';
}
