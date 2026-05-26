<?php

use App\Jobs\ImportQuestionsJob;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('rejects multiple choice questions without exactly four options', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->for(Subject::factory()->create(['level' => 'js']))->create();

    $response = $this
        ->actingAs($user)
        ->post('/questions', [
            'type' => 'multiple_choice',
            'topic_id' => $topic->id,
            'content' => 'Sample question',
            'level' => 'js',
            'options' => json_encode([
                ['content' => 'A', 'is_correct' => true],
                ['content' => 'B', 'is_correct' => false],
            ]),
        ]);

    $response->assertSessionHasErrors(['options']);
    expect(Question::query()->count())->toBe(0);
});

it('rejects multiple choice questions with multiple correct answers', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->for(Subject::factory()->create(['level' => 'js']))->create();

    $response = $this
        ->actingAs($user)
        ->post('/questions', [
            'type' => 'multiple_choice',
            'topic_id' => $topic->id,
            'content' => 'Sample question',
            'level' => 'js',
            'options' => json_encode([
                ['content' => 'A', 'is_correct' => true],
                ['content' => 'B', 'is_correct' => true],
                ['content' => 'C', 'is_correct' => false],
                ['content' => 'D', 'is_correct' => false],
            ]),
        ]);

    $response->assertSessionHasErrors(['options']);
    expect(Question::query()->count())->toBe(0);
});

it('rejects theory questions without a marking scheme', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->for(Subject::factory()->create(['level' => 'js']))->create();

    $response = $this
        ->actingAs($user)
        ->post('/questions', [
            'type' => 'theory',
            'topic_id' => $topic->id,
            'content' => 'Explain gravity',
            'level' => 'js',
            'marking_scheme' => json_encode([]),
        ]);

    $response->assertSessionHasErrors(['marking_scheme']);
    expect(Question::query()->count())->toBe(0);
});

it('queues question imports on confirm', function () {
    Queue::fake();

    $user = User::factory()->create();

    $previewRows = [[
        'subject_name' => 'Mathematics',
        'topic_name' => 'Algebra',
        'type' => 'multiple_choice',
        'content' => str_repeat('Long content ', 20),
        'image_url' => null,
        'explanation' => 'Because it balances.',
        'level' => 'js',
        'options' => ['1', '2', '3', '4'],
        'correct_answer' => 'B',
        'marking_scheme' => [],
        'valid' => true,
        'errors' => [],
        'index' => 2,
    ]];

    $response = $this
        ->actingAs($user)
        ->withSession([
            'import_preview' => [
                'rows' => $previewRows,
                'new_subjects' => ['Mathematics'],
                'new_topics' => ['Algebra'],
            ],
        ])
        ->post('/questions/import/confirm');

    $response->assertRedirect('/questions');
    $response->assertSessionHas('success');

    Queue::assertPushed(ImportQuestionsJob::class, function (ImportQuestionsJob $job) use ($user) {
        return $job->createdBy === $user->id
            && $job->defaultLevel === 'js'
            && $job->rows[0]['content'] === str_repeat('Long content ', 20)
            && $job->rows[0]['explanation'] === 'Because it balances.';
    });
});
