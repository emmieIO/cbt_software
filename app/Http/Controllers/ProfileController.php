<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show(Request $request): Response
    {
        $user = $request->user()->load(['schoolClass']);
        
        // Ensure roles are passed as a flat array of names for frontend consistency
        $userData = array_merge($user->toArray(), [
            'roles' => $user->getRoleNames(),
        ]);

        return Inertia::render('Profile/Show', [
            'user' => $userData,
        ]);
    }
}
