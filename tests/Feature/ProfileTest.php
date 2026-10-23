<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the authenticated users profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Show')
            ->where('profile.name', $user->name)
            ->where('profile.username', $user->username)
            ->where('profile.email', $user->email));
});

it('updates the authenticated users profile', function () {
    $user = User::factory()->create([
        'password' => 'existing-password',
    ]);

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => 'Updated User',
            'username' => 'updated.user',
            'email' => 'updated@example.com',
            'current_password' => 'existing-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->name)->toBe('Updated User')
        ->and($user->username)->toBe('updated.user')
        ->and($user->email)->toBe('updated@example.com')
        ->and(Hash::check('new-password', $user->password))->toBeTrue();
});

it('requires the current password before changing the password', function () {
    $user = User::factory()->create([
        'password' => 'existing-password',
    ]);

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'current_password' => 'incorrect-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect('/profile')
        ->assertSessionHasErrors('current_password');
});
