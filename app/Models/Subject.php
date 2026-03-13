<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    /** @use HasFactory<\Database\Factories\SubjectFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'description',
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
            'level' => \App\Enums\ClassLevel::class,
        ];
    }

    /**
     * Get the topics for the subject.
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }
}
