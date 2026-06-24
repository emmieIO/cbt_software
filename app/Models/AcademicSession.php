<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AcademicSessionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property bool $is_active
 * @property Collection<int, Exam> $exams
 */
class AcademicSession extends Model
{
    /** @use HasFactory<AcademicSessionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    /**
     * @return HasMany<Exam, $this>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
