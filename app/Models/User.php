<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\GeneratesApplicationId;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use GeneratesApplicationId, HasFactory, HasRoles, HasUlids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'school_id', // Kept for students, deprecated for staff in favor of schools()
        'school_class_id',
        'status',
        'is_active',
    ];

    /**
     * Get the schools/branches the user is assigned to.
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the primary school branch for the user.
     */
    public function getPrimarySchoolAttribute(): ?School
    {
        return $this->schools()->wherePivot('is_primary', true)->first() ?? $this->schools()->first();
    }

    /**
     * Get the school the user belongs to (Legacy/Student support).
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the class the user (student) belongs to.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Get the exams assigned to this student.
     */
    public function assignedExams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_user')->withTimestamps();
    }

    /**
     * Get all exam attempts for the user.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Get the latest exam attempt for the user.
     */
    public function latestAttempt(): HasOne
    {
        return $this->hasOne(ExamAttempt::class)->latestOfMany();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
