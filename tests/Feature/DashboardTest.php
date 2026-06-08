<?php

use App\Models\Question;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('sends rich question previews to the dashboard', function () {
    $user = User::factory()->create();
    $content = '<p>What is <strong>2 + 2</strong>?</p><p><span data-type="inline-math" data-latex="x^2"></span></p>';

    Question::factory()->create([
        'content' => $content,
        'created_by' => $user->id,
    ]);

    $this
        ->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('recentQuestions.0.content', $content)
        );
});
