<?php

use App\Models\Question;
use App\Models\ExamTitle;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;

it('rejects exam generation with duplicate question ids', function () {
    $user = User::factory()->create();
    ExamTitle::factory()->create(['name' => 'Midterm']);
    $subject = Subject::factory()->create(['level' => 'js']);
    $topic = Topic::factory()->for($subject)->create();
    $question = Question::factory()->for($topic)->for($user, 'creator')->create([
        'level' => 'js',
        'type' => 'multiple_choice',
    ]);

    $response = $this
        ->actingAs($user)
        ->post('/exams/generate', [
            'title' => 'Midterm',
            'instructions' => 'Answer all questions.',
            'question_ids' => [$question->id, $question->id],
        ]);

    $response->assertSessionHasErrors(['question_ids.1']);
});

it('rejects exam generation with a title that is not configured', function () {
    $user = User::factory()->create();
    $subject = Subject::factory()->create(['level' => 'js']);
    $topic = Topic::factory()->for($subject)->create();
    $question = Question::factory()->for($topic)->for($user, 'creator')->create([
        'level' => 'js',
        'type' => 'multiple_choice',
    ]);

    $response = $this
        ->actingAs($user)
        ->post('/exams/generate', [
            'title' => 'Unconfigured Exam',
            'instructions' => 'Answer all questions.',
            'question_ids' => [$question->id],
        ]);

    $response->assertSessionHasErrors(['title']);
});

it('rejects export generation when no questions are requested', function () {
    $user = User::factory()->create();
    ExamTitle::factory()->create(['name' => 'Mock Exam']);
    $subject = Subject::factory()->create(['level' => 'js']);

    $response = $this
        ->actingAs($user)
        ->post('/export/generate', [
            'title' => 'Mock Exam',
            'subject_id' => $subject->id,
            'level' => 'js',
            'instructions' => 'Answer carefully.',
            'mcq_count' => 0,
            'theory_count' => 0,
        ]);

    $response->assertSessionHasErrors(['mcq_count']);
});

it('allows admins to manage exam titles', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this
        ->actingAs($admin)
        ->post('/exam-titles', [
            'name' => 'Promotion Examination',
            'is_active' => true,
        ])
        ->assertRedirect();

    $examTitle = ExamTitle::query()->where('name', 'Promotion Examination')->firstOrFail();

    expect($examTitle->is_active)->toBeTrue();

    $this
        ->actingAs($admin)
        ->put("/exam-titles/{$examTitle->id}", [
            'name' => 'Promotion Assessment',
            'is_active' => false,
        ])
        ->assertRedirect();

    expect($examTitle->fresh())
        ->name->toBe('Promotion Assessment')
        ->is_active->toBeFalse();
});

it('prevents non-admin users from deleting exam titles', function () {
    $user = User::factory()->create();
    $examTitle = ExamTitle::factory()->create();

    $this
        ->actingAs($user)
        ->delete("/exam-titles/{$examTitle->id}")
        ->assertForbidden();

    $this->assertModelExists($examTitle);
});
