<?php

namespace Tests\Feature;

use App\Enums\ClassLevel;
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

class StaffBatchQuestionModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $examinerRole = Role::findOrCreate('examiner', 'web');
        $examinerRole->category = 'staff';
        $examinerRole->save();

        foreach (['access:staff-portal', 'bank:create', 'sys:manage_settings'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            if ($permission !== 'sys:manage_settings') {
                $examinerRole->givePermissionTo($permission);
            }
        }
    }

    public function test_batch_store_rejects_topic_subject_mismatch(): void
    {
        $school = School::factory()->create(['type' => 'secondary']);
        $staff = User::factory()->create(['school_id' => $school->id]);
        $staff->assignRole('examiner');

        $class = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'level' => ClassLevel::SECONDARY,
        ]);

        $subjectA = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);
        $subjectB = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);
        $topicFromB = Topic::factory()->create([
            'subject_id' => $subjectB->id,
            'school_class_id' => $class->id,
        ]);

        $response = $this->actingAs($staff)->post('/staff/questions/batch', [
            'questions' => [[
                'subject_id' => $subjectA->id,
                'topic_id' => $topicFromB->id,
                'school_class_id' => $class->id,
                'content' => 'What is the correct answer for this mismatch case?',
                'type' => 'multiple_choice',
                'difficulty' => 'medium',
                'options' => [
                    ['content' => 'A', 'is_correct' => true],
                    ['content' => 'B', 'is_correct' => false],
                ],
            ]],
        ]);

        $response->assertSessionHasErrors(['questions.0.topic_id']);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_batch_store_rejects_cross_branch_class_for_staff(): void
    {
        $staffSchool = School::factory()->create(['type' => 'secondary']);
        $otherSchool = School::factory()->create(['type' => 'secondary']);
        $staff = User::factory()->create(['school_id' => $staffSchool->id]);
        $staff->assignRole('examiner');

        $classFromOtherBranch = SchoolClass::factory()->create([
            'school_id' => $otherSchool->id,
            'level' => ClassLevel::SECONDARY,
        ]);
        $subject = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);
        $topic = Topic::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $classFromOtherBranch->id,
        ]);

        $response = $this->actingAs($staff)->post('/staff/questions/batch', [
            'questions' => [[
                'subject_id' => $subject->id,
                'topic_id' => $topic->id,
                'school_class_id' => $classFromOtherBranch->id,
                'content' => 'This row should fail branch boundary validation.',
                'type' => 'multiple_choice',
                'difficulty' => 'medium',
                'options' => [
                    ['content' => 'A', 'is_correct' => true],
                    ['content' => 'B', 'is_correct' => false],
                ],
            ]],
        ]);

        $response->assertSessionHasErrors(['questions.0.school_class_id']);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_batch_store_creates_questions_for_valid_rows(): void
    {
        $school = School::factory()->create(['type' => 'secondary']);
        $staff = User::factory()->create(['school_id' => $school->id]);
        $staff->assignRole('examiner');

        $class = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'level' => ClassLevel::SECONDARY,
        ]);
        $subject = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);
        $topic = Topic::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
        ]);

        $response = $this->actingAs($staff)->post('/staff/questions/batch', [
            'questions' => [[
                'subject_id' => $subject->id,
                'topic_id' => $topic->id,
                'school_class_id' => $class->id,
                'content' => 'A fully valid question row that should be persisted.',
                'type' => 'multiple_choice',
                'difficulty' => 'medium',
                'options' => [
                    ['content' => 'Correct', 'is_correct' => true],
                    ['content' => 'Wrong', 'is_correct' => false],
                ],
            ]],
        ]);

        $response->assertRedirect(route('staff.questions.index'));
        $this->assertDatabaseCount('questions', 1);
        $this->assertSame(2, Question::firstOrFail()->options()->count());
    }
}
