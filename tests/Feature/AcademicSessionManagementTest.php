<?php

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\User;

it('allows admins to manage academic sessions and keeps only one active', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $existing = AcademicSession::factory()->active()->create();

    $this
        ->actingAs($admin)
        ->post('/academic-sessions', [
            'name' => '2030/2031',
            'starts_at' => '2030-09-01',
            'ends_at' => '2031-08-31',
            'is_active' => true,
        ])
        ->assertRedirect();

    $created = AcademicSession::query()->where('name', '2030/2031')->firstOrFail();

    expect($created->is_active)->toBeTrue()
        ->and($existing->fresh()->is_active)->toBeFalse();

    $this
        ->actingAs($admin)
        ->put("/academic-sessions/{$created->id}", [
            'name' => '2030/2031',
            'starts_at' => '2030-09-08',
            'ends_at' => '2031-08-28',
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($created->fresh()->starts_at->format('Y-m-d'))->toBe('2030-09-08');
});

it('prevents non-admin users from managing academic sessions', function () {
    $user = User::factory()->create();
    $session = AcademicSession::factory()->create();

    $this->actingAs($user)->get('/academic-sessions')->assertForbidden();
    $this->actingAs($user)->delete("/academic-sessions/{$session->id}")->assertForbidden();
});

it('prevents deleting a session that is used by an exam', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $session = AcademicSession::factory()->active()->create();

    Exam::query()->create([
        'title' => 'Mock Examination',
        'academic_session_id' => $session->id,
        'subject_name' => 'English Language',
        'level' => 'js',
        'instructions' => 'Answer all questions.',
        'mcq_count' => 0,
        'theory_count' => 0,
        'total_marks' => 0,
        'created_by' => $admin->id,
    ]);

    $this
        ->actingAs($admin)
        ->delete("/academic-sessions/{$session->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertModelExists($session);
});
