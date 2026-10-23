<?php

namespace App\Models;

use App\Traits\GeneratesApplicationId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $id
 * @property string $name
 * @property string $role
 * @property array<int, string>|null $permissions
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use GeneratesApplicationId, HasFactory, HasUlids, Notifiable;

    const ROLE_ADMIN = 'admin';

    const ROLE_UPLOADER = 'uploader';

    const PERMISSION_CREATE_QUESTIONS = 'questions.create';

    const PERMISSION_EDIT_QUESTIONS = 'questions.edit';

    const PERMISSION_GENERATE_QUESTIONS = 'questions.generate';

    const PERMISSION_MANAGE_KNOWLEDGE_BASE = 'knowledge.manage';

    const PERMISSION_APPROVE_KNOWLEDGE_DOCUMENTS = 'knowledge.approve';

    const PERMISSION_REVIEW_CURRICULUM = 'curriculum.review';

    const QUESTION_PERMISSIONS = [
        self::PERMISSION_CREATE_QUESTIONS,
        self::PERMISSION_EDIT_QUESTIONS,
        self::PERMISSION_GENERATE_QUESTIONS,
    ];

    const ASSIGNABLE_PERMISSIONS = [
        ...self::QUESTION_PERMISSIONS,
        self::PERMISSION_MANAGE_KNOWLEDGE_BASE,
        self::PERMISSION_APPROVE_KNOWLEDGE_DOCUMENTS,
        self::PERMISSION_REVIEW_CURRICULUM,
    ];

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isUploader(): bool
    {
        return $this->role === self::ROLE_UPLOADER;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->isAdmin() || in_array($permission, $this->permissions ?? [], true);
    }

    public function canCreateQuestions(): bool
    {
        return $this->hasPermission(self::PERMISSION_CREATE_QUESTIONS);
    }

    public function canEditQuestions(): bool
    {
        return $this->hasPermission(self::PERMISSION_EDIT_QUESTIONS);
    }

    public function canGenerateQuestions(): bool
    {
        return $this->hasPermission(self::PERMISSION_GENERATE_QUESTIONS);
    }

    public function canManageKnowledgeBase(): bool
    {
        return $this->hasPermission(self::PERMISSION_MANAGE_KNOWLEDGE_BASE);
    }

    public function canAccessKnowledgeBase(): bool
    {
        return $this->canManageKnowledgeBase() || $this->canApproveKnowledgeDocuments() || $this->canReviewCurriculum();
    }

    public function canReviewCurriculum(): bool
    {
        return $this->hasPermission(self::PERMISSION_REVIEW_CURRICULUM);
    }

    public function canApproveKnowledgeDocuments(): bool
    {
        return $this->hasPermission(self::PERMISSION_APPROVE_KNOWLEDGE_DOCUMENTS);
    }
}
