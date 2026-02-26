<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait GeneratesApplicationId
{
    public static function bootGeneratesApplicationId(): void
    {
        static::creating(function (User $user) {
            // 1. If username is blank, generate an APP ID
            if (empty($user->username)) {
                $user->username = self::generateUniqueApplicationId();
            }

            // 2. Always sync school_id with username for consistency in entrance records
            if (empty($user->school_id)) {
                $user->school_id = $user->username;
            }
        });
    }

    private static function generateUniqueApplicationId(): string
    {
        $year = date('Y');
        $prefix = "APP/{$year}/";

        $lastId = DB::table('users')
            ->where('school_id', 'like', "{$prefix}%")
            ->orderBy('school_id', 'desc')
            ->value('school_id');

        $sequence = 1;
        if ($lastId && str_contains($lastId, '/')) {
            $lastSequence = (int) substr($lastId, strrpos($lastId, '/') + 1);
            $sequence = $lastSequence + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
