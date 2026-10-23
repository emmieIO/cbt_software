<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = request()->user();

        return Inertia::render('Profile/Show', [
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $user->permissions ?? [],
                'created_at' => $user->created_at?->toDateString(),
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $payload = $request->payload();

        if (empty($payload['password'])) {
            unset($payload['password']);
        }

        $request->user()->update($payload);

        return back()->with('success', 'Profile updated successfully.');
    }
}
