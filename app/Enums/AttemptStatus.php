<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case ONGOING = 'ongoing';
    case SUBMITTED = 'submitted';
    case GRADED = 'graded';
    case TIMED_OUT = 'timed_out';
}
