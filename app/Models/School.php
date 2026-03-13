<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'address',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => \App\Enums\ClassLevel::class,
            'contact_phone' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the users that belong to the school.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
