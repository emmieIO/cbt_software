<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->can('bank:manage')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('bank:view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Question $question): bool
    {
        return $this->isAssigned($user, $question);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('bank:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Question $question): bool
    {
        return $this->isAssigned($user, $question);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Question $question): bool
    {
        return $this->isAssigned($user, $question);
    }

    /**
     * Check if the user is assigned to the question's subject and class/batch.
     */
    protected function isAssigned(User $user, Question $question): bool
    {
        // Legacy teacher assignment table has been retired; enforce branch-based ownership instead.
        if (! $user->school_id) {
            return $question->created_by === $user->id;
        }

        $question->loadMissing('schoolClass');
        $classSchoolId = $question->schoolClass?->school_id;

        if ($classSchoolId && $classSchoolId !== $user->school_id) {
            return false;
        }

        return true;
    }
}
