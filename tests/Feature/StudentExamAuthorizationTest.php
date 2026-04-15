<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Option;
use App\Models\Question;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ExamService;
use App\Services\Student\StudentPortalService;
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

        /** @var Role $candidateRole */
        $candidateRole = Role::findOrCreate('candidate', 'web');
        $candidateRole->setAttribute('category', 'student');
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
        /** @var Exam $exam */
        $exam = $attempt->exam()->firstOrFail();
        /** @var Question $question */
        $question = $exam->questions()->firstOrFail();
        $optionOrders = is_array($attempt->metadata) ? ($attempt->metadata['option_orders'] ?? []) : [];
        /** @var string|null $firstOptionId */
        $firstOptionId = data_get($optionOrders, $question->id.'.0');

        $response = $this->actingAs($intruder)->patch(route('student.exams.save-answer', $attempt->id), [
            'question_id' => $question->id,
            'option_id' => $firstOptionId,
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_submit_another_students_attempt(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();
        /** @var Exam $exam */
        $exam = $attempt->exam()->firstOrFail();
        /** @var Question $question */
        $question = $exam->questions()->firstOrFail();
        $optionOrders = is_array($attempt->metadata) ? ($attempt->metadata['option_orders'] ?? []) : [];
        /** @var string|null $firstOptionId */
        $firstOptionId = data_get($optionOrders, $question->id.'.0');

        $response = $this->actingAs($intruder)->post(route('student.exams.submit', $attempt->id), [
            'answers' => [
                $question->id => $firstOptionId,
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_view_another_students_result(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();
        /** @var Exam $exam */
        $exam = $attempt->exam()->firstOrFail();
        /** @var Question $question */
        $question = $exam->questions()->firstOrFail();
        $optionOrders = is_array($attempt->metadata) ? ($attempt->metadata['option_orders'] ?? []) : [];
        /** @var string $correctOptionId */
        $correctOptionId = data_get($optionOrders, $question->id.'.0');

        app(ExamService::class)->submitAttempt($attempt, [
            $question->id => $correctOptionId,
        ]);

        $response = $this->actingAs($intruder)->get(route('student.exams.result', $attempt->id));

        $response->assertForbidden();
    }

    public function test_student_cannot_start_exam_outside_their_visibility_scope(): void
    {
        $studentClass = SchoolClass::factory()->create();
        $otherClass = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create(['school_class_id' => $studentClass->id]);
        $student->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_class_id' => $otherClass->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $otherClass->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $response = $this->from(route('student.exams.index'))
            ->actingAs($student)
            ->post(route('student.exams.start', $exam->id));

        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error', 'This examination is not available for your class.');
        $this->assertDatabaseMissing('exam_attempts', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
        ]);
    }

    public function test_student_can_start_exam_when_explicitly_assigned(): void
    {
        $studentClass = SchoolClass::factory()->create();
        $otherClass = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create(['school_class_id' => $studentClass->id]);
        $student->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_class_id' => $otherClass->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $exam->users()->attach($student->id);

        $question = Question::factory()->create(['school_class_id' => $otherClass->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $response = $this->actingAs($student)->post(route('student.exams.start', $exam->id));

        $attempt = ExamAttempt::query()
            ->where('user_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        $this->assertNotNull($attempt);
        $response->assertRedirect(route('student.exams.show', $attempt->id));
    }

    public function test_student_can_start_exam_when_current_session_enrollment_matches_exam_class(): void
    {
        $profileClass = SchoolClass::factory()->create();
        $enrolledClass = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create(['school_class_id' => $profileClass->id]);
        $student->assignRole('candidate');

        ClassEnrollment::query()->create([
            'user_id' => $student->id,
            'school_class_id' => $enrolledClass->id,
            'academic_session_id' => $session->id,
        ]);

        $exam = Exam::factory()->create([
            'school_class_id' => $enrolledClass->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $enrolledClass->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $response = $this->actingAs($student)->post(route('student.exams.start', $exam->id));

        $attempt = ExamAttempt::query()
            ->where('user_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        $this->assertNotNull($attempt);
        $response->assertRedirect(route('student.exams.show', $attempt->id));
    }

    public function test_student_can_start_exam_when_profile_class_matches_even_if_enrollment_differs(): void
    {
        $profileClass = SchoolClass::factory()->create();
        $otherEnrolledClass = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create(['school_class_id' => $profileClass->id]);
        $student->assignRole('candidate');

        ClassEnrollment::query()->create([
            'user_id' => $student->id,
            'school_class_id' => $otherEnrolledClass->id,
            'academic_session_id' => $session->id,
        ]);

        $exam = Exam::factory()->create([
            'school_class_id' => $profileClass->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $profileClass->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $response = $this->actingAs($student)->post(route('student.exams.start', $exam->id));

        $attempt = ExamAttempt::query()
            ->where('user_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        $this->assertNotNull($attempt);
        $response->assertRedirect(route('student.exams.show', $attempt->id));
    }

    public function test_student_can_start_live_exam_even_when_exam_session_is_not_globally_current(): void
    {
        $class = SchoolClass::factory()->create();
        AcademicSession::factory()->create(['is_current' => true]);
        $examSession = AcademicSession::factory()->create(['is_current' => false]);
        $student = User::factory()->create(['school_class_id' => $class->id]);
        $student->assignRole('candidate');

        ClassEnrollment::query()->create([
            'user_id' => $student->id,
            'school_class_id' => $class->id,
            'academic_session_id' => $examSession->id,
        ]);

        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $examSession->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $class->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $response = $this->actingAs($student)->post(route('student.exams.start', $exam->id));

        $attempt = ExamAttempt::query()
            ->where('user_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        $this->assertNotNull($attempt);
        $response->assertRedirect(route('student.exams.show', $attempt->id));
    }

    public function test_student_can_start_exam_when_branch_and_class_match(): void
    {
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
        ]);
        $student->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $class->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $response = $this->actingAs($student)->post(route('student.exams.start', $exam->id));

        $attempt = ExamAttempt::query()
            ->where('user_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        $this->assertNotNull($attempt);
        $response->assertRedirect(route('student.exams.show', $attempt->id));
    }

    public function test_student_cannot_start_exam_when_branch_differs_even_if_class_matches(): void
    {
        $studentSchool = School::factory()->create();
        $examSchool = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $studentSchool->id]);
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create([
            'school_id' => $studentSchool->id,
            'school_class_id' => $class->id,
        ]);
        $student->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_id' => $examSchool->id,
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $class->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $response = $this->from(route('student.exams.index'))
            ->actingAs($student)
            ->post(route('student.exams.start', $exam->id));

        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error', 'This examination is not available for your school branch.');
    }

    public function test_student_cannot_start_exam_without_questions(): void
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create(['school_class_id' => $class->id]);
        $student->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $response = $this->from(route('student.exams.index'))
            ->actingAs($student)
            ->post(route('student.exams.start', $exam->id));

        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error', 'This examination is not ready yet because no questions have been assigned.');
        $this->assertDatabaseMissing('exam_attempts', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
        ]);
    }

    public function test_student_cannot_start_exam_after_exam_window_closes(): void
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create(['school_class_id' => $class->id]);
        $student->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHours(2),
            'end_time' => now()->subMinute(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $class->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $response = $this->from(route('student.exams.index'))
            ->actingAs($student)
            ->post(route('student.exams.start', $exam->id));

        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error', 'This examination window has closed.');
        $this->assertDatabaseMissing('exam_attempts', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
        ]);
    }

    public function test_expired_exam_without_attempt_is_marked_as_missed(): void
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create(['school_class_id' => $class->id]);
        $student->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHours(3),
            'end_time' => now()->subHour(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $class->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $exams = app(StudentPortalService::class)->getAvailableExams($student);
        $listedExam = $exams->firstWhere('id', $exam->id);

        $this->assertNotNull($listedExam);
        $this->assertSame('missed', $listedExam['availability_status']);
    }

    public function test_live_exam_without_start_time_is_not_visible_to_students(): void
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $student = User::factory()->create(['school_class_id' => $class->id]);
        $student->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => null,
            'end_time' => now()->addHour(),
        ]);

        $question = Question::factory()->create(['school_class_id' => $class->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $exam->questions()->attach($question->id);

        $exams = app(StudentPortalService::class)->getAvailableExams($student);

        $this->assertNull($exams->firstWhere('id', $exam->id));
    }

    public function test_student_cannot_save_answer_after_time_expires(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();
        unset($intruder);

        $attempt->update([
            'started_at' => now()->subMinutes(90),
        ]);

        /** @var Exam $exam */
        $exam = $attempt->exam()->firstOrFail();
        /** @var Question $question */
        $question = $exam->questions()->firstOrFail();
        $optionId = $question->options()->firstOrFail()->id;

        $response = $this->from(route('student.exams.show', $attempt->id))
            ->actingAs($owner)
            ->patch(route('student.exams.save-answer', $attempt->id), [
                'question_id' => $question->id,
                'option_id' => $optionId,
            ]);

        $response->assertRedirect(route('student.exams.show', $attempt->id));
        $response->assertSessionHas('error', 'Time is up for this examination. Please submit your attempt.');

        $attempt->refresh();
        $this->assertArrayNotHasKey($question->id, $attempt->metadata['saved_answers'] ?? []);
    }

    public function test_student_cannot_save_answer_for_question_outside_attempt(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();
        unset($intruder);

        $rogueQuestion = Question::factory()->create();
        $rogueOption = Option::factory()->create([
            'question_id' => $rogueQuestion->id,
            'is_correct' => false,
        ]);

        $response = $this->from(route('student.exams.show', $attempt->id))
            ->actingAs($owner)
            ->patch(route('student.exams.save-answer', $attempt->id), [
                'question_id' => $rogueQuestion->id,
                'option_id' => $rogueOption->id,
            ]);

        $response->assertRedirect(route('student.exams.show', $attempt->id));
        $response->assertSessionHas('error', 'Invalid answer selection for this examination.');

        $attempt->refresh();
        $this->assertArrayNotHasKey($rogueQuestion->id, $attempt->metadata['saved_answers'] ?? []);
    }

    public function test_student_cannot_save_answer_for_option_outside_question_order(): void
    {
        [$owner, $intruder, $attempt] = $this->makeAttemptFixture();
        unset($intruder);

        /** @var Exam $exam */
        $exam = $attempt->exam()->firstOrFail();
        /** @var Question $question */
        $question = $exam->questions()->firstOrFail();
        $rogueOption = Option::factory()->create([
            'question_id' => Question::factory()->create()->id,
            'is_correct' => false,
        ]);

        $response = $this->from(route('student.exams.show', $attempt->id))
            ->actingAs($owner)
            ->patch(route('student.exams.save-answer', $attempt->id), [
                'question_id' => $question->id,
                'option_id' => $rogueOption->id,
            ]);

        $response->assertRedirect(route('student.exams.show', $attempt->id));
        $response->assertSessionHas('error', 'Invalid answer selection for this examination.');

        $attempt->refresh();
        $this->assertArrayNotHasKey($question->id, $attempt->metadata['saved_answers'] ?? []);
    }

    /**
     * @return array{0: User, 1: User, 2: ExamAttempt}
     */
    protected function makeAttemptFixture(): array
    {
        $class = SchoolClass::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $owner = User::factory()->create(['school_class_id' => $class->id]);
        $intruder = User::factory()->create(['school_class_id' => $class->id]);

        $owner->assignRole('candidate');
        $intruder->assignRole('candidate');

        $exam = Exam::factory()->create([
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'status' => 'live',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $question = Question::factory()->create(['school_class_id' => $class->id]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        Option::factory()->create(['question_id' => $question->id, 'is_correct' => false]);
        $exam->questions()->attach($question->id);

        $attempt = app(ExamService::class)->startExam($owner, $exam);

        return [$owner, $intruder, $attempt];
    }
}
