<?php

namespace App\Models;

use App\Enums\ClassLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolClassFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'level',
    ];

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
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
