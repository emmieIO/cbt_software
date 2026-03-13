<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\GeneratesApplicationId;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'school_id',
        'school_class_id',
        'prospective_class_id',
        'status',
        'is_active',
    ];

    /**
     * Get the school the user belongs to.
     */
    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the class the user (student) belongs to.
     */
    public function schoolClass(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Get the prospective batch the user (candidate) belongs to.
     */
    public function prospectiveClass(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProspectiveClass::class);
    }

    /**
     * Get the teaching assignments for this user (if staff).
     */
    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'user_id');
    }

    /**
     * Get the current academic session's assignments for this staff member.
     */
    public function currentAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'user_id')
            ->whereHas('academicSession', fn ($q) => $q->where('is_current', true));
    }

    /**
     * Get all exam attempts for the user.
     */
    public function attempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Get the latest exam attempt for the user.
     */
    public function latestAttempt(): \Illuminate\Database\Eloquent\Relations\HasOne
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
