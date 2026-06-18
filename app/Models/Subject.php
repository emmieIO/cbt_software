<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string|null $level
 */
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
    ];

    /**
     * Get the topics for the subject.
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }
}
