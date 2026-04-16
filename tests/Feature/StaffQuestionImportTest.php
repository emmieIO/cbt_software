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
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffQuestionImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $examinerRole = Role::findOrCreate('examiner', 'web');
        $examinerRole->setAttribute('category', 'staff');
        $examinerRole->save();

        foreach (['access:staff-portal', 'bank:create', 'sys:manage_settings'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            if ($permission !== 'sys:manage_settings') {
                $examinerRole->givePermissionTo($permission);
            }
        }
    }

    public function test_staff_can_import_questions_with_setup_first_csv(): void
    {
        $school = School::factory()->create(['type' => 'secondary']);
        $staff = User::factory()->create(['school_id' => $school->id]);
        $staff->assignRole('examiner');

        $class = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'level' => ClassLevel::SECONDARY,
        ]);
        $subject = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);

        $file = UploadedFile::fake()->createWithContent('questions.csv', implode("\n", [
            'topic_name,content,explanation,type,option_a,option_b,option_c,option_d,correct_option_letter,image_url',
            '"Number Bases","What is 10 in binary?","10 base ten equals 1010 base two.","multiple_choice","1010","1110","1001","1100","A",""',
            '"Number Bases","Which value equals 15 in binary?","15 base ten equals 1111 base two.","multiple_choice","1110","1111","1101","1011","B",""',
        ]));

        $response = $this->actingAs($staff)->post(route('staff.questions.import'), [
            'file' => $file,
            'level' => ClassLevel::SECONDARY->value,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'difficulty' => 'medium',
            'question_type' => 'multiple_choice',
        ]);

        $response->assertRedirect(route('staff.questions.index'));
        $response->assertSessionHas('success', '2 questions imported successfully.');
        $this->assertDatabaseCount('questions', 2);

        $topic = Topic::query()->where('name', 'Number Bases')->first();
        $this->assertNotNull($topic);
        $this->assertSame($subject->id, $topic->subject_id);
        $this->assertSame($class->id, $topic->school_class_id);
        $this->assertSame(4, Question::query()->firstOrFail()->options()->count());
    }

    public function test_staff_cannot_import_questions_into_class_outside_branch(): void
    {
        $staffSchool = School::factory()->create(['type' => 'secondary']);
        $otherSchool = School::factory()->create(['type' => 'secondary']);
        $staff = User::factory()->create(['school_id' => $staffSchool->id]);
        $staff->assignRole('examiner');

        $class = SchoolClass::factory()->create([
            'school_id' => $otherSchool->id,
            'level' => ClassLevel::SECONDARY,
        ]);
        $subject = Subject::factory()->create(['level' => ClassLevel::SECONDARY->value]);

        $file = UploadedFile::fake()->createWithContent('questions.csv', implode("\n", [
            'topic_name,content,explanation,type,option_a,option_b,option_c,option_d,correct_option_letter,image_url',
            '"Number Bases","What is 10 in binary?","10 base ten equals 1010 base two.","multiple_choice","1010","1110","1001","1100","A",""',
        ]));

        $response = $this->actingAs($staff)->post(route('staff.questions.import'), [
            'file' => $file,
            'level' => ClassLevel::SECONDARY->value,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'difficulty' => 'medium',
            'question_type' => 'multiple_choice',
        ]);

        $response->assertSessionHasErrors(['school_class_id']);
        $this->assertDatabaseCount('questions', 0);
    }
}
