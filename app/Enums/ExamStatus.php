<?php

namespace App\Enums;

enum ExamStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case LIVE = 'live';
    case CLOSED = 'closed';
}
