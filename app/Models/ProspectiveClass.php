<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProspectiveClass extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get the candidates assigned to this batch.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(User::class, 'prospective_class_id');
    }

    /**
     * Get the exams assigned to this batch.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'prospective_class_id');
    }
}