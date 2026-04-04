<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentExamAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $candidateRole = Role::findOrCreate('candidate', 'web');
        $candidateRole->category = 'student';
        $candidateRole->save();

        foreach (['access:student-portal', 'exam:take', 'results:view', 'sys:manage_settings'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            if ($permission !== 'sys:manage_settings') {
                $candidateRole->givePermissionTo($permission);
            }
        }
    }

    public function test_student_cannot_view_another_students_attempt(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();

        $response = $this->actingAs($intruder)->get(route('student.exams.show', $attempt->id));

        $response->assertForbidden();
    }

    public function test_student_cannot_save_answer_on_another_students_attempt(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();

        $response = $this->actingAs($intruder)->patch(route('student.exams.save-answer', $attempt->id), [
            'question_id' => $attempt->exam->questions->first()->id,
            'option_id' => $attempt->metadata['option_orders'][$attempt->exam->questions->first()->id][0] ?? null,
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_submit_another_students_attempt(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();
        $question = $attempt->exam->questions->first();

        $response = $this->actingAs($intruder)->post(route('student.exams.submit', $attempt->id), [
            'answers' => [
                $question->id => $attempt->metadata['option_orders'][$question->id][0] ?? null,
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_view_another_students_result(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();
        $question = $attempt->exam->questions->first();
        $correctOptionId = $attempt->metadata['option_orders'][$question->id][0];

        app(ExamService::class)->submitAttempt($attempt, [
            $question->id => $correctOptionId,
        ]);

        $response = $this->actingAs($intruder)->get(route('student.exams.result', $attempt->id));

        $response->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User, 2: ExamAttempt}
     */
    protected function makeAttemptFixture(): array
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $owner->assignRole('candidate');
        $intruder->assignRole('candidate');

        $exam = Exam::factory()->create();
        $question = Question::factory()->create();
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => false]);
        $exam->questions()->attach($question->id);

        $attempt = app(ExamService::class)->startExam($owner, $exam);

        return [$owner, $intruder, $attempt];
    }
}
