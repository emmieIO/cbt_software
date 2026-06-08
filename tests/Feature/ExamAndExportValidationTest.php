<?php

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;

it('rejects exam generation with duplicate question ids', function () {
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
            'title' => 'Midterm',
            'instructions' => 'Answer all questions.',
            'question_ids' => [$question->id, $question->id],
        ]);

    $response->assertSessionHasErrors(['question_ids.1']);
});

it('rejects export generation when no questions are requested', function () {
    $user = User::factory()->create();
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
