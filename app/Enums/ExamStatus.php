<?php

namespace App\Enums;

enum ExamStatus: string
{
    case DRAFT = 'draft';
    case LIVE = 'live';
    case CLOSED = 'closed';
}
