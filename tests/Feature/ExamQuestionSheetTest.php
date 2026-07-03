<?php

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;

it('divides the question sheet into sections by question type', function () {
    $user = User::factory()->create();
    $academicSession = AcademicSession::query()->where('name', '2025/2026')->firstOrFail();
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
        'academic_session_id' => $academicSession->id,
        'subject_name' => 'Physics',
        'level' => 'ss',
        'instructions' => 'Answer all questions.',
        'duration' => '2 Hours',
        'mcq_count' => 1,
        'theory_count' => 2,
        'total_marks' => 8,
        'created_by' => $user->id,
    ]);

    $exam->questions()->attach($mcq->id, ['section' => 'mcq', 'sort_order' => 0]);
    $exam->questions()->attach($shortAnswer->id, ['section' => 'theory', 'sort_order' => 0]);
    $exam->questions()->attach($theory->id, ['section' => 'theory', 'sort_order' => 1]);

    $response = $this
        ->actingAs($user)
        ->get("/exams/{$exam->id}/preview-html/questions");

    $response->assertSuccessful();
    $response->assertViewIs('pdf.exam-questions');
    $response->assertSee('Back to Exam');
    $response->assertSee('Print Question Paper');
    $response->assertSee('data:image/png;base64,', false);
    $response->assertSee('CHRISLAND SCHOOLS');
    $response->assertSee('TESTS, EXAMINATIONS AND');
    $response->assertSee('ACADEMIC RECORDS UNIT');
    $response->assertSee('MOCK EXAMINATION');
    $response->assertSee('2025/2026 ACADEMIC SESSION');
    $response->assertSee('DURATION: 2 HOURS');
    $response->assertSee('SCORE');
    $response->assertDontSee('1 | Page');
    $response->assertSee('Section A (MULTIPLE CHOICE)');
    $response->assertSee('Section B (SHORT ANSWER)');
    $response->assertSee('Section C (THEORY)');
    $response->assertSee('<table class="objective-columns">', false);
    $response->assertSee('1.');
    $response->assertSee('2.');
    $response->assertSee('3.');

    $answerSheet = $this
        ->actingAs($user)
        ->get("/exams/{$exam->id}/preview-html/answer-sheet");

    $answerSheet->assertSuccessful();
    $answerSheet->assertSee('Multiple Choice Answer Sheet');
    $answerSheet->assertSee('TOTAL SCORE');
    $answerSheet->assertSee("FOR TEACHER'S USE", false);
    $answerSheet->assertSee('Marks Obtained');
    $answerSheet->assertSee('data:image/png;base64,', false);

    $answerKey = $this
        ->actingAs($user)
        ->get("/exams/{$exam->id}/preview-html/answer-key");

    $answerKey->assertSuccessful();
    $answerKey->assertSee('Multiple Choice Answer Key');
    $answerKey->assertSee('Confidential - For Examiners Only');
    $answerKey->assertSee('Filled circle = correct option');
    $answerKey->assertSee('data:image/png;base64,', false);

    $markingGuide = $this
        ->actingAs($user)
        ->get("/exams/{$exam->id}/preview-html/marking-guide");

    $markingGuide->assertSuccessful();
    $markingGuide->assertSee('Written Examination Marking Guide');
    $markingGuide->assertSee('Expected Answer / Marking Point');
    $markingGuide->assertSee('Prepared by / Signature');
    $markingGuide->assertSee('data:image/png;base64,', false);
});
