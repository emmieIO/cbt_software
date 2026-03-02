<?php

namespace App\Enums;

enum Term: string
{
    case FIRST = 'first';
    case SECOND = 'second';
    case THIRD = 'third';

    public function label(): string
    {
        return match ($this) {
            self::FIRST => 'First Term',
            self::SECOND => 'Second Term',
            self::THIRD => 'Third Term',
        };
    }
}
