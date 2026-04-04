<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;

class ProfileService
{
    /**
     * Build the complete profile payload used by the profile page.
     */
    public function buildProfileData(User $user): array
    {
        $user->load(['school', 'schoolClass', 'schools']);

        $isStaff = $user->can('bank:view');
        $assignments = $isStaff
            ? $user->schools->map(fn (School $school) => [
                'id' => $school->id,
                'subject' => ['name' => 'All Subjects'],
                'school_class' => ['name' => $school->name],
            ])->values()
            : [];

        return array_merge($user->toArray(), [
            'roles' => $user->getRoleNames(),
            'assignments' => $assignments,
        ]);
    }
}
