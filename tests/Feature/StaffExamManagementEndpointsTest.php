<?php

namespace Tests\Feature;

use App\Enums\ClassLevel;
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
            'exam:edit',
            'sys:manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $examinerRole = Role::findOrCreate('examiner', 'web');
        $examinerRole->category = 'staff';
        $examinerRole->save();
        $examinerRole->syncPermissions(['access:staff-portal', 'exam:edit']);

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
}
