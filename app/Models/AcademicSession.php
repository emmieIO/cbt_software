<?php

namespace App\Models;

use App\Enums\Term;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicSessionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'term',
        'is_current',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'term' => Term::class,
            'is_current' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected $appends = ['term_label'];

    public function getTermLabelAttribute(): string
    {
        return $this->term->label();
    }

    /**
     * Scope a query to only include the current session.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
