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
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('staff') || $user->hasRole('subject_lead');
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
        return $user->hasRole('staff') || $user->hasRole('subject_lead');
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
        return $user->currentAssignments()
            ->where(function ($query) use ($question) {
                if (! $question->prospective_class_id) {
                    // Regular Question: Teacher must be assigned to this regular class
                    $query->whereNotNull('school_class_id')
                        ->where('school_class_id', $question->school_class_id)
                        ->where(function ($subQ) use ($question) {
                            $subQ->where('subject_id', $question->topic->subject_id)
                                ->orWhereNull('subject_id');
                        });
                } else {
                    // Entrance Question: Teacher must be assigned to this entrance batch
                    $query->whereNotNull('prospective_class_id')
                        ->where('prospective_class_id', $question->prospective_class_id)
                        ->where(function ($subQ) use ($question) {
                            $subQ->where('subject_id', $question->topic->subject_id)
                                ->orWhereNull('subject_id');
                        });
                }
            })
            ->exists();
    }
}
