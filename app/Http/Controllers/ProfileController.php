<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $profileService) {}

    /**
     * Display the authenticated user's profile.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Profile/Show', [
            'user' => $this->profileService->buildProfileData($request->user()),
        ]);
    }
}
