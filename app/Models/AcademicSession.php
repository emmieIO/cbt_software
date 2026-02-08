<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicSessionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'is_current',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Scope a query to only include the current session.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
