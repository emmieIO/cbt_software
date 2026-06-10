<?php

use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;

it('divides the question sheet into sections by question type', function () {
    $user = User::factory()->create();
    $subject = Subject::factory()->create(['name' => 'Physics', 'level' => 'ss']);
    $topic = Topic::factory()->for($subject)->create();

    $mcq = Question::factory()->for($topic)->for($user, 'creator')->create([
        'content' => 'What is the unit of force?',
        'level' => 'ss',
        'type' => 'multiple_choice',
    ]);
    Option::factory()->for($mcq)->create(['content' => 'Newton', 'is_correct' => true]);
    Option::factory()->for($mcq)->create(['content' => 'Joule']);
    Option::factory()->for($mcq)->create(['content' => 'Watt']);
    Option::factory()->for($mcq)->create(['content' => 'Pascal']);

    $shortAnswer = Question::factory()->for($topic)->for($user, 'creator')->create([
        'content' => 'State Newton\'s first law.',
        'level' => 'ss',
        'type' => 'short_answer',
        'marking_scheme' => [['point' => 'Mentions inertia', 'weight' => 2]],
    ]);

    $theory = Question::factory()->for($topic)->for($user, 'creator')->create([
        'content' => 'Explain the conservation of momentum.',
        'level' => 'ss',
        'type' => 'theory',
        'marking_scheme' => [['point' => 'Explains closed systems', 'weight' => 5]],
    ]);

    $exam = Exam::query()->create([
        'title' => 'Mock Examination',
        'subject_name' => 'Physics',
        'level' => 'ss',
        'instructions' => 'Answer all questions.',
        'mcq_count' => 1,
        'theory_count' => 2,
        'total_marks' => 8,
        'created_by' => $user->id,
    ]);

    $exam->questions()->attach($mcq->id, ['section' => 'mcq', 'sort_order' => 0]);
    $exam->questions()->attach($shortAnswer->id, ['section' => 'theory', 'sort_order' => 0]);
    $exam->questions()->attach($theory->id, ['section' => 'theory', 'sort_order' => 1]);

    $this
        ->actingAs($user)
        ->get("/exams/{$exam->id}/preview-html/questions")
        ->assertSuccessful()
        ->assertSee('Section A: Multiple Choice')
        ->assertSee('Section B: Short Answer')
        ->assertSee('Section C: Theory')
        ->assertSee('1.')
        ->assertSee('2.')
        ->assertSee('3.');
});
