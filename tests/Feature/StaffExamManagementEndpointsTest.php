<?php

namespace Tests\Feature;

use App\Enums\ClassLevel;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\Question;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffExamManagementEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'access:staff-portal',
            'exam:create',
            'exam:edit',
            'sys:manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        /** @var Role $examinerRole */
        $examinerRole = Role::findOrCreate('examiner', 'web');
        $examinerRole->setAttribute('category', 'staff');
        $examinerRole->save();
        $examinerRole->syncPermissions(['access:staff-portal', 'exam:create', 'exam:edit']);

        $school = School::factory()->create(['type' => 'secondary']);

        $this->staff = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);
        $this->staff->assignRole($examinerRole);
    }

    public function test_update_questions_requires_question_ids_array(): void
    {
        $exam = Exam::factory()->create();

        $response = $this->actingAs($this->staff)->post("/staff/exams/{$exam->id}/questions", [
            'question_ids' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['question_ids']);
    }

    public function test_update_questions_syncs_exam_questions_when_valid(): void
    {
        $subject = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);
        $schoolClass = SchoolClass::factory()->create(['level' => ClassLevel::SECONDARY]);
        $topic = Topic::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
        ]);

        $exam = Exam::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'created_by' => $this->staff->id,
        ]);

        $q1 = Question::factory()->create([
            'topic_id' => $topic->id,
            'school_class_id' => $schoolClass->id,
            'created_by' => $this->staff->id,
        ]);
        $q2 = Question::factory()->create([
            'topic_id' => $topic->id,
            'school_class_id' => $schoolClass->id,
            'created_by' => $this->staff->id,
        ]);

        $response = $this->actingAs($this->staff)->post("/staff/exams/{$exam->id}/questions", [
            'question_ids' => [$q1->id, $q2->id],
        ]);

        $response->assertRedirect(route('staff.exams.show', $exam->id));

        $exam->refresh();
        $this->assertCount(2, $exam->questions);
        $this->assertDatabaseHas('exam_questions', [
            'exam_id' => $exam->id,
            'question_id' => $q1->id,
        ]);
        $this->assertDatabaseHas('exam_questions', [
            'exam_id' => $exam->id,
            'question_id' => $q2->id,
        ]);
    }

    public function test_ai_select_questions_validates_count_bounds(): void
    {
        $exam = Exam::factory()->create();

        $response = $this->actingAs($this->staff)->post("/staff/exams/{$exam->id}/ai-select", [
            'count' => 0,
        ]);

        $response->assertSessionHasErrors(['count']);
    }

    public function test_ai_select_questions_selects_requested_number_when_available(): void
    {
        $subject = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);
        $schoolClass = SchoolClass::factory()->create(['level' => ClassLevel::SECONDARY]);
        $topic = Topic::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
        ]);

        $exam = Exam::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'created_by' => $this->staff->id,
        ]);

        Question::factory()->count(3)->create([
            'topic_id' => $topic->id,
            'school_class_id' => $schoolClass->id,
            'created_by' => $this->staff->id,
            'last_used_at' => null,
        ]);

        $response = $this->actingAs($this->staff)->post("/staff/exams/{$exam->id}/ai-select", [
            'count' => 2,
        ]);

        $response->assertSessionHas('success');
        $exam->refresh();
        $this->assertCount(2, $exam->questions);
    }

    public function test_store_exam_rejects_subject_that_does_not_match_selected_class_level(): void
    {
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $school = School::factory()->create(['type' => ClassLevel::SECONDARY]);
        $secondaryClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'level' => ClassLevel::SECONDARY,
        ]);
        $primarySubject = Subject::factory()->create(['level' => ClassLevel::PRIMARY->value]);

        $response = $this->actingAs($this->staff)->post(route('staff.exams.store'), [
            'title' => 'Bad Level Exam',
            'school_id' => $school->id,
            'subject_id' => $primarySubject->id,
            'school_class_id' => $secondaryClass->id,
            'duration' => 60,
            'type' => 'terminal',
            'start_time' => now()->addHour()->toDateTimeString(),
            'end_time' => now()->addHours(2)->toDateTimeString(),
        ]);

        $response->assertSessionHasErrors(['subject_id']);
    }

    public function test_store_exam_rejects_topic_that_does_not_belong_to_selected_class(): void
    {
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $school = School::factory()->create(['type' => ClassLevel::SECONDARY]);
        $targetClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'level' => ClassLevel::SECONDARY,
        ]);
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'level' => ClassLevel::SECONDARY,
        ]);
        $subject = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);
        $topic = Topic::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $otherClass->id,
        ]);

        $response = $this->actingAs($this->staff)->post(route('staff.exams.store'), [
            'title' => 'Broken Composition Exam',
            'school_id' => $school->id,
            'school_class_id' => $targetClass->id,
            'duration' => 60,
            'type' => 'terminal',
            'start_time' => now()->addHour()->toDateTimeString(),
            'end_time' => now()->addHours(2)->toDateTimeString(),
            'compositions' => [
                [
                    'subject_id' => $subject->id,
                    'topic_id' => $topic->id,
                    'question_count' => 10,
                    'marks_per_question' => 1,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['compositions.0.topic_id']);
    }

    public function test_staff_can_update_exam_status(): void
    {
        $exam = Exam::factory()->create([
            'created_by' => $this->staff->id,
            'status' => 'draft',
        ]);
        $question = Question::factory()->create(['created_by' => $this->staff->id]);
        $exam->questions()->attach($question->id);

        $response = $this->actingAs($this->staff)->put(route('staff.exams.status.update', $exam->id), [
            'status' => 'live',
        ]);

        $response->assertRedirect(route('staff.exams.show', $exam->id));
        $response->assertSessionHas('success');
        $liveStatus = $exam->fresh()->status;
        $this->assertSame('live', is_string($liveStatus) ? $liveStatus : $liveStatus?->value);
    }

    public function test_staff_cannot_make_exam_live_without_questions(): void
    {
        $exam = Exam::factory()->create([
            'created_by' => $this->staff->id,
            'status' => 'draft',
        ]);

        $response = $this->from(route('staff.exams.show', $exam->id))
            ->actingAs($this->staff)
            ->put(route('staff.exams.status.update', $exam->id), [
                'status' => 'live',
            ]);

        $response->assertRedirect(route('staff.exams.show', $exam->id));
        $response->assertSessionHas('error', 'You cannot make this examination live until questions have been allocated.');
        $draftStatus = $exam->fresh()->status;
        $this->assertSame('draft', is_string($draftStatus) ? $draftStatus : $draftStatus?->value);
    }

    public function test_staff_cannot_make_exam_live_without_start_time(): void
    {
        $exam = Exam::factory()->create([
            'created_by' => $this->staff->id,
            'status' => 'draft',
            'start_time' => null,
        ]);
        $question = Question::factory()->create(['created_by' => $this->staff->id]);
        $exam->questions()->attach($question->id);

        $response = $this->from(route('staff.exams.show', $exam->id))
            ->actingAs($this->staff)
            ->put(route('staff.exams.status.update', $exam->id), [
                'status' => 'live',
            ]);

        $response->assertRedirect(route('staff.exams.show', $exam->id));
        $response->assertSessionHas('error', 'You cannot make this examination live until a start date and time has been set.');
        $draftStatus = $exam->fresh()->status;
        $this->assertSame('draft', is_string($draftStatus) ? $draftStatus : $draftStatus?->value);
    }

    public function test_staff_cannot_update_exam_with_past_start_time(): void
    {
        $exam = Exam::factory()->create([
            'created_by' => $this->staff->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->staff)->put(route('staff.exams.update', $exam->id), [
            'title' => $exam->title,
            'school_id' => $exam->school_id,
            'subject_id' => $exam->subject_id,
            'school_class_id' => $exam->school_class_id,
            'duration' => $exam->duration,
            'type' => is_string($exam->type) ? $exam->type : $exam->type->value,
            'status' => 'draft',
            'start_time' => now()->subHour()->toDateTimeString(),
            'end_time' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertSessionHasErrors(['start_time']);
    }
}
