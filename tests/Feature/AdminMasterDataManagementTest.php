<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMasterDataManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'access:admin-portal',
            'sys:manage_settings',
            'admin:manage_setup',
            'admin:manage_curriculum',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('super_admin', 'web');
        $adminRole->category = 'admin';
        $adminRole->save();
        $adminRole->syncPermissions($permissions);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);
    }

    public function test_create_session_with_is_current_unsets_existing_current_session(): void
    {
        $existingCurrent = AcademicSession::factory()->create([
            'name' => '2025/2026',
            'term' => 'first',
            'is_current' => true,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/school-setup/sessions', [
            'name' => '2026/2027',
            'term' => 'second',
            'start_date' => '2026-01-10',
            'end_date' => '2026-04-10',
            'is_current' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('academic_sessions', [
            'id' => $existingCurrent->id,
            'is_current' => false,
        ]);
        $this->assertDatabaseHas('academic_sessions', [
            'name' => '2026/2027',
            'term' => 'second',
            'is_current' => true,
        ]);
    }

    public function test_cannot_delete_current_session(): void
    {
        $current = AcademicSession::factory()->create(['is_current' => true]);

        $response = $this->actingAs($this->admin)->delete("/admin/school-setup/sessions/{$current->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('academic_sessions', ['id' => $current->id]);
    }

    public function test_class_name_must_be_unique_per_level(): void
    {
        SchoolClass::factory()->create([
            'name' => 'JSS1',
            'level' => 'secondary',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/school-setup/classes', [
            'name' => 'JSS1',
            'level' => 'secondary',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_same_class_name_allowed_across_different_levels(): void
    {
        SchoolClass::factory()->create([
            'name' => 'Grade 1',
            'level' => 'primary',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/school-setup/classes', [
            'name' => 'Grade 1',
            'level' => 'secondary',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('school_classes', [
            'name' => 'Grade 1',
            'level' => 'secondary',
        ]);
    }

    public function test_cannot_delete_class_when_questions_exist(): void
    {
        $class = SchoolClass::factory()->create(['level' => 'secondary']);
        $subject = Subject::factory()->create(['level' => 'secondary']);
        $topic = Topic::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
        ]);

        Question::factory()->create([
            'topic_id' => $topic->id,
            'school_class_id' => $class->id,
            'created_by' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/school-setup/classes/{$class->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('school_classes', ['id' => $class->id]);
    }

    public function test_cannot_delete_subject_when_topics_exist(): void
    {
        $subject = Subject::factory()->create(['level' => 'secondary']);
        Topic::factory()->create(['subject_id' => $subject->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/curriculum/subjects/{$subject->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_cannot_delete_topic_when_questions_exist(): void
    {
        $class = SchoolClass::factory()->create(['level' => 'secondary']);
        $subject = Subject::factory()->create(['level' => 'secondary']);
        $topic = Topic::factory()->create([
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
        ]);

        Question::factory()->create([
            'topic_id' => $topic->id,
            'school_class_id' => $class->id,
            'created_by' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/curriculum/topics/{$topic->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('topics', ['id' => $topic->id]);
    }
}
