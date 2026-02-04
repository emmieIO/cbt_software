<?php

namespace App\Enums;

enum QuestionType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case TRUE_FALSE = 'true_false';
}
