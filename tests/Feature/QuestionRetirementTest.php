<?php

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamTitle;
use App\Models\Option;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\ExamGenerationService;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->freezeTime();
    $this->user = User::factory()->create(['permissions' => [User::PERMISSION_EDIT_QUESTIONS]]);
    $this->subject = Subject::factory()->create(['level' => 'js']);
    $topic = Topic::factory()->for($this->subject)->create(['class_level' => '7']);
    $this->questions = Question::factory()->for($topic)->for($this->user, 'creator')->state([
        'level' => 'js',
        'class_level' => '7',
    ]);
    $this->examData = [
        'title' => ExamTitle::factory()->create(['name' => 'Midterm'])->name,
        'academic_session_id' => AcademicSession::factory()->active()->create(['name' => 'Retirement Test Session'])->id,
        'class_level' => '7',
    ];
    $this->actingAs($this->user);
});

it('allows question editors and admins to retire and restore without changing question data', function (bool $isAdmin) {
    if ($isAdmin) {
        $this->user->update(['role' => User::ROLE_ADMIN, 'permissions' => []]);
    }

    $question = $this->questions->has(Option::factory()->count(4))->create([
        'used_count' => 8,
        'last_used_at' => now()->subDay(),
        'image_path' => 'questions/original.png',
    ]);
    $original = $question->only(['content', 'image_path', 'marking_scheme', 'used_count', 'last_used_at', 'deleted_at']);
    $optionIds = $question->options->modelKeys();
    $returnUrl = route('questions.index', ['status' => 'all', 'search' => 'original']);

    $this->from($returnUrl)->patch(route('questions.retire', $question))->assertRedirect($returnUrl)->assertSessionHas('success');

    expect($question->refresh()->retired_at)->not->toBeNull()
        ->and($question->retired_by)->toBe($this->user->id)
        ->and($question->only(array_keys($original)))->toEqual($original)
        ->and($question->options->modelKeys())->toBe($optionIds);

    $retiredAt = $question->retired_at;
    $otherCreator = User::factory()->create(['permissions' => [User::PERMISSION_EDIT_QUESTIONS]]);
    $this->travel(1)->hours();
    $this->actingAs($otherCreator)->patch(route('questions.retire', $question))->assertRedirect();

    expect($question->refresh()->retired_at->equalTo($retiredAt))->toBeTrue()
        ->and($question->retired_by)->toBe($this->user->id);

    $this->patch(route('questions.restore', $question))->assertRedirect($returnUrl)->assertSessionHas('success');
    $this->patch(route('questions.restore', $question))->assertRedirect();

    expect($question->refresh()->retired_at)->toBeNull()
        ->and($question->retired_by)->toBeNull()
        ->and($question->only(array_keys($original)))->toEqual($original)
        ->and($question->options->modelKeys())->toBe($optionIds);
})->with([false, true]);

it('requires question editing permission for retirement actions', function (string $action, array $permissions) {
    $question = $this->questions->create(['retired_at' => $action === 'restore' ? now() : null]);
    $retiredAt = $question->retired_at;
    $user = User::factory()->create(['permissions' => $permissions]);

    $this->actingAs($user)->patch(route('questions.'.$action, $question))->assertForbidden();

    expect($question->refresh()->retired_at)->toEqual($retiredAt);
})->with(['retire', 'restore'])->with([
    'no permissions' => [[]],
    'create only' => [[User::PERMISSION_CREATE_QUESTIONS]],
    'generate only' => [[User::PERMISSION_GENERATE_QUESTIONS]],
]);

it('requires authentication for retirement actions', function (string $action) {
    $question = $this->questions->create();
    auth()->forgetGuards();

    $this->patch(route('questions.'.$action, $question))->assertRedirect(route('login'));
    expect($question->refresh()->retired_at)->toBeNull();
})->with(['retire', 'restore']);

it('filters question retirement state with active as the default', function (?string $status, string $normalizedStatus) {
    $active = $this->questions->create(['used_count' => 9]);
    $retired = $this->questions->create(['retired_at' => now(), 'retired_by' => $this->user->id]);
    $deleted = $this->questions->create();
    $deleted->delete();
    $expectedIds = match ($normalizedStatus) {
        'all' => [$active->id, $retired->id],
        'retired' => [$retired->id],
        default => [$active->id],
    };

    $this->get(route('questions.index', $status === null ? [] : ['status' => $status]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Questions/Index')
            ->where('filters.status', $normalizedStatus)
            ->where('questions.total', count($expectedIds))
            ->where('questions.data', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all() === collect($expectedIds)->sort()->values()->all()));
})->with([
    'default' => [null, 'active'],
    'active' => ['active', 'active'],
    'retired' => ['retired', 'retired'],
    'all' => ['all', 'all'],
    'invalid' => ['unknown', 'active'],
]);

it('preserves retirement filters through search and pagination without arbitrary usage filtering', function () {
    $this->questions->count(2)->create(['content' => 'Matching retired question', 'retired_at' => now()]);
    $this->questions->create(['content' => 'Different retired question', 'retired_at' => now()]);
    $this->questions->create(['content' => 'Matching active question', 'used_count' => 0]);
    $this->questions->create(['content' => 'Matching used question', 'used_count' => 12]);

    $this->get(route('questions.index', [
        'status' => 'retired',
        'search' => 'Matching',
        'subject_id' => $this->subject->id,
        'level' => 'js',
        'class_level' => '7',
        'per_page' => 1,
    ]))->assertInertia(fn (Assert $page) => $page
        ->where('questions.total', 2)
        ->has('questions.data', 1)
        ->where('questions.next_page_url', fn ($url) => str_contains($url, 'status=retired') && str_contains($url, 'search=Matching')));

    $this->get(route('questions.index', ['overused' => 1]))->assertInertia(fn (Assert $page) => $page
        ->where('questions.total', 2)
        ->missing('filters.overused'));
});

it('excludes retired questions of every type from the selection pool without changing usage', function () {
    $activeIds = [];
    foreach (['multiple_choice', 'short_answer', 'theory'] as $type) {
        $activeIds[] = $this->questions->create(['type' => $type, 'used_count' => 6])->id;
        $this->questions->create(['type' => $type, 'retired_at' => now(), 'class_level' => null]);
    }

    $response = $this->getJson(route('exams.pool', [
        'subject_id' => $this->subject->id,
        'level' => 'js',
        'class_level' => '7',
    ]))->assertSuccessful()->assertJsonCount(1, 'mcqs')->assertJsonCount(2, 'theory');

    $response->assertJsonPath('mcqs.0.retired_at', null)->assertJsonPath('theory.0.retired_at', null);

    expect(collect([...$response->json('mcqs'), ...$response->json('theory')])->pluck('id')->sort()->values()->all())
        ->toBe(collect($activeIds)->sort()->values()->all())
        ->and(Question::query()->whereIn('id', $activeIds)->pluck('used_count')->all())->toBe([6, 6, 6]);
});

it('includes only the current exams retained retired questions in its edit pool', function (string $type) {
    $question = $this->questions->create(['type' => $type, 'class_level' => '8']);
    $exam = app(ExamGenerationService::class)->generateFromSelection(
        [...$this->examData, 'question_ids' => [$question->id]], $this->user->id,
    );
    $this->patch(route('questions.retire', $question))->assertRedirect();
    $unattachedRetired = $this->questions->create(['type' => $type, 'retired_at' => now()]);
    $active = $this->questions->create(['type' => $type]);
    $otherExam = app(ExamGenerationService::class)->generateFromSelection(
        [...$this->examData, 'question_ids' => [$active->id]], $this->user->id,
    );
    $query = ['subject_id' => $this->subject->id, 'level' => 'js', 'class_level' => '7'];
    $section = $type === 'multiple_choice' ? 'mcqs' : 'theory';
    $viewer = User::factory()->create(['permissions' => []]);

    $this->actingAs($viewer)->get(route('exams.edit-questions', $exam))->assertSuccessful();
    $response = $this->getJson(route('exams.pool', [...$query, 'exam_id' => $exam->id]))
        ->assertSuccessful()->assertJsonCount(2, $section);
    $items = collect($response->json($section))->keyBy('id');

    expect($items->keys()->all())->toEqualCanonicalizing([$active->id, $question->id])
        ->and($items[$question->id]['retired_at'])->toBe($question->refresh()->retired_at->toISOString())
        ->and($items[$active->id]['retired_at'])->toBeNull()
        ->and($question->used_count)->toBe(1)
        ->and($unattachedRetired->refresh()->used_count)->toBe(0);

    foreach ([null, $otherExam->id] as $examId) {
        $this->getJson(route('exams.pool', [...$query, 'exam_id' => $examId]))
            ->assertSuccessful()->assertJsonCount(1, $section)->assertJsonPath($section.'.0.id', $active->id);
    }

    $this->postJson(route('exams.generate'), [...$this->examData, 'exam_id' => $exam->id, 'question_ids' => [$question->id]])
        ->assertUnprocessable()->assertJsonValidationErrors('question_ids.0');
})->with(['multiple_choice', 'short_answer', 'theory']);

it('validates the optional edit pool exam lookup', function (mixed $examId) {
    $this->getJson(route('exams.pool', [
        'exam_id' => $examId,
        'subject_id' => $this->subject->id,
        'level' => 'js',
        'class_level' => '7',
    ]))->assertUnprocessable()->assertJsonValidationErrors('exam_id');
})->with(['missing exam' => ['missing'], 'non-string id' => [['invalid']]]);

it('requires the existing exam authentication for edit pools', function () {
    $question = $this->questions->create();
    $exam = app(ExamGenerationService::class)->generateFromSelection(
        [...$this->examData, 'question_ids' => [$question->id]], $this->user->id,
    );
    auth()->forgetGuards();

    $this->getJson(route('exams.pool', [
        'exam_id' => $exam->id,
        'subject_id' => $this->subject->id,
        'level' => 'js',
        'class_level' => '7',
    ]))->assertUnauthorized();
});

it('rejects crafted manual selections containing retired or deleted questions atomically', function (string $state) {
    $active = $this->questions->create();
    $unavailable = $this->questions->create(['retired_at' => $state === 'retired' ? now() : null]);
    if ($state === 'deleted') {
        $unavailable->delete();
    }

    $this->postJson(route('exams.generate'), [...$this->examData, 'question_ids' => [$active->id, $unavailable->id]])
        ->assertUnprocessable()->assertJsonValidationErrors('question_ids.1');

    expect(Exam::query()->count())->toBe(0)
        ->and($active->refresh()->used_count)->toBe(0)
        ->and($active->last_used_at)->toBeNull();
})->with(['retired', 'deleted']);

it('rejects retired selections at the service boundary as well as request validation', function () {
    $question = $this->questions->create(['retired_at' => now()]);

    expect(fn () => app(ExamGenerationService::class)->generateFromSelection(
        [...$this->examData, 'question_ids' => [$question->id]], $this->user->id,
    ))->toThrow(ValidationException::class);

    expect(Exam::query()->count())->toBe(0)->and($question->refresh()->used_count)->toBe(0);
});

it('selects only active questions for random generation and increments each addition once', function () {
    $activeIds = [];
    foreach (['multiple_choice', 'short_answer', 'theory'] as $type) {
        $attributes = ['type' => $type, 'marking_scheme' => $type === 'multiple_choice' ? null : [['point' => 'Correct answer', 'weight' => 2]]];
        $activeIds[] = $this->questions->create([...$attributes, 'used_count' => 5])->id;
        $this->questions->create([...$attributes, 'retired_at' => now()]);
    }

    $this->post(route('export.generate'), [
        ...$this->examData,
        'subject_id' => $this->subject->id,
        'level' => 'js',
        'mcq_count' => 100,
        'theory_count' => 20,
    ])->assertRedirect();

    $exam = Exam::query()->sole();
    expect($exam->questions->modelKeys())->toEqualCanonicalizing($activeIds)
        ->and($exam->mcq_count)->toBe(1)
        ->and($exam->theory_count)->toBe(2)
        ->and($exam->total_marks)->toBe(5)
        ->and(Question::query()->whereIn('id', $activeIds)->pluck('used_count')->all())->toBe([6, 6, 6])
        ->and(Question::retired()->sum('used_count'))->toBe(0);
});

it('preserves retired questions and options in historical exam views and previews', function (string $type) {
    $question = $this->questions->has(Option::factory()->count($type === 'multiple_choice' ? 4 : 0))->create([
        'content' => 'Original historical question',
        'type' => $type,
        'marking_scheme' => $type === 'multiple_choice' ? null : [['point' => 'Original marking point', 'weight' => 2]],
    ]);
    $data = [...$this->examData, 'question_ids' => [$question->id]];
    $this->post(route('exams.generate'), $data)->assertSuccessful();
    $exam = Exam::query()->sole();
    $lastUsedAt = $question->refresh()->last_used_at;
    $originalExam = $exam->getAttributes();
    $optionIds = $question->options->modelKeys();

    $this->patch(route('questions.retire', $question))->assertRedirect();
    $section = $type === 'multiple_choice' ? 'mcqs' : 'theory';
    $this->get(route('exams.show', $exam))->assertInertia(fn (Assert $page) => $page
        ->has('exam.'.$section, 1)
        ->where('exam.'.$section.'.0.id', $question->id)
        ->where('exam.'.$section.'.0.content', 'Original historical question'));
    $this->get(route('exams.edit-questions', $exam))->assertInertia(fn (Assert $page) => $page
        ->where('exam.selected_question_ids', [$question->id]));
    $this->get(route('exams.preview-html.questions', $exam))->assertSuccessful()->assertSee('Original historical question');
    $this->post(route('exams.update-questions', $exam), $data)->assertRedirect(route('exams.show', $exam));

    expect($exam->refresh()->getAttributes())->toEqual($originalExam)
        ->and($exam->questions->modelKeys())->toBe([$question->id])
        ->and($question->refresh()->options->modelKeys())->toBe($optionIds)
        ->and($question->used_count)->toBe(1)
        ->and($question->last_used_at)->toEqual($lastUsedAt);
})->with(['multiple_choice', 'short_answer', 'theory']);

it('counts cumulative additions but not retained selections or removals on exam updates', function () {
    $retained = $this->questions->create(['used_count' => 4]);
    $removed = $this->questions->create(['used_count' => 8]);
    $this->post(route('exams.generate'), [...$this->examData, 'question_ids' => [$retained->id, $removed->id]])->assertSuccessful();
    $exam = Exam::query()->sole();
    $retainedLastUsedAt = $retained->refresh()->last_used_at;
    $removedLastUsedAt = $removed->refresh()->last_used_at;
    $this->patch(route('questions.retire', $retained))->assertRedirect();
    $addedIds = [];
    foreach (['multiple_choice', 'short_answer', 'theory'] as $type) {
        $addedIds[] = $this->questions->create(['type' => $type, 'marking_scheme' => [['point' => 'Answer', 'weight' => 2]]])->id;
    }
    $data = [...$this->examData, 'question_ids' => [$retained->id, ...$addedIds]];
    $this->travel(1)->hours();

    $this->post(route('exams.update-questions', $exam), $data)->assertRedirect();
    $this->post(route('exams.update-questions', $exam), $data)->assertRedirect();

    expect($retained->refresh()->used_count)->toBe(5)
        ->and($retained->last_used_at)->toEqual($retainedLastUsedAt)
        ->and($removed->refresh()->used_count)->toBe(9)
        ->and($removed->last_used_at)->toEqual($removedLastUsedAt)
        ->and(Question::query()->whereIn('id', $addedIds)->pluck('used_count')->all())->toBe([1, 1, 1])
        ->and(Question::query()->whereIn('id', $addedIds)->where('last_used_at', now())->count())->toBe(3);

    $this->post(route('exams.update-questions', $exam), [...$this->examData, 'question_ids' => [$retained->id]])->assertRedirect();
    $this->post(route('exams.update-questions', $exam), $data)->assertRedirect();

    expect(Question::query()->whereIn('id', $addedIds)->pluck('used_count')->all())->toBe([2, 2, 2])
        ->and($retained->refresh()->used_count)->toBe(5)
        ->and($removed->refresh()->used_count)->toBe(9);
});

it('cannot attach a retired question from another exam even through a crafted update', function () {
    $active = $this->questions->create();
    $retired = $this->questions->create();
    $service = app(ExamGenerationService::class);
    $exam = $service->generateFromSelection([...$this->examData, 'question_ids' => [$active->id]], $this->user->id);
    $otherExam = $service->generateFromSelection([...$this->examData, 'question_ids' => [$retired->id]], $this->user->id);
    $this->patch(route('questions.retire', $retired))->assertRedirect();
    $data = [...$this->examData, 'question_ids' => [$active->id, $retired->id]];

    $this->postJson(route('exams.update-questions', $exam), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('question_ids.1');
    expect(fn () => $service->updateSelection($exam, $data))->toThrow(ValidationException::class);

    expect($exam->fresh()->questions->modelKeys())->toBe([$active->id])
        ->and($otherExam->fresh()->questions->modelKeys())->toBe([$retired->id])
        ->and($active->refresh()->used_count)->toBe(1)
        ->and($retired->refresh()->used_count)->toBe(1);
});

it('cannot reattach a removed retired question until it has been restored', function () {
    $retired = $this->questions->create();
    $active = $this->questions->create();
    $data = [...$this->examData, 'question_ids' => [$retired->id, $active->id]];
    $this->post(route('exams.generate'), $data)->assertSuccessful();
    $exam = Exam::query()->sole();
    $this->patch(route('questions.retire', $retired))->assertRedirect();
    $this->post(route('exams.update-questions', $exam), [...$this->examData, 'question_ids' => [$active->id]])->assertRedirect();
    $this->postJson(route('exams.update-questions', $exam), $data)->assertUnprocessable()->assertJsonValidationErrors('question_ids.0');

    expect($retired->refresh()->used_count)->toBe(1)->and($active->refresh()->used_count)->toBe(1);

    $this->patch(route('questions.restore', $retired))->assertRedirect();
    $this->post(route('exams.update-questions', $exam), $data)->assertRedirect();
    $this->post(route('exams.generate'), [...$this->examData, 'question_ids' => [$retired->id]])->assertSuccessful();

    expect($retired->refresh()->used_count)->toBe(3)
        ->and($active->refresh()->used_count)->toBe(1)
        ->and(Exam::query()->count())->toBe(2);
});
