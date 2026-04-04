<?php

namespace App\Models;

use App\Enums\ClassLevel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $school_id
 */
class SchoolClass extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolClassFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'school_id',
        'name',
        'slug',
        'level',
        'branch',
    ];

    /**
     * Get the school this class belongs to.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => ClassLevel::class,
        ];
    }

    /**
     * Get the questions for the class.
     *
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
