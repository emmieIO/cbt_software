<?php

namespace App\Models;

use Database\Factories\ExamTitleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property bool $is_active
 */
class ExamTitle extends Model
{
    /** @use HasFactory<ExamTitleFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
