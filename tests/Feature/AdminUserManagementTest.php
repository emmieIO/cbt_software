<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
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
            'staff:view',
            'staff:create',
            'staff:edit',
            'staff:delete',
            'student:view',
            'student:create',
            'student:edit',
            'student:delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('super_admin', 'web');
        $adminRole->category = 'admin';
        $adminRole->save();
        $adminRole->syncPermissions($permissions);

        $examinerRole = Role::findOrCreate('examiner', 'web');
        $examinerRole->category = 'staff';
        $examinerRole->save();

        $invigilatorRole = Role::findOrCreate('invigilator', 'web');
        $invigilatorRole->category = 'staff';
        $invigilatorRole->save();

        $candidateRole = Role::findOrCreate('candidate', 'web');
        $candidateRole->category = 'student';
        $candidateRole->save();

        $prefectRole = Role::findOrCreate('prefect', 'web');
        $prefectRole->category = 'student';
        $prefectRole->save();

        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);
    }

    public function test_admin_can_create_staff_and_sync_multiple_school_assignments(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/users/staff', [
            'name' => 'Jane Examiner',
            'email' => 'jane.examiner@example.com',
            'username' => 'jane.examiner',
            'school_ids' => [$schoolA->id, $schoolB->id],
            'primary_school_id' => $schoolB->id,
            'role' => 'examiner',
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $staff = User::where('email', 'jane.examiner@example.com')->firstOrFail();
        $this->assertTrue($staff->hasRole('examiner'));
        $this->assertDatabaseHas('school_user', [
            'user_id' => $staff->id,
            'school_id' => $schoolA->id,
            'is_primary' => false,
        ]);
        $this->assertDatabaseHas('school_user', [
            'user_id' => $staff->id,
            'school_id' => $schoolB->id,
            'is_primary' => true,
        ]);
    }

    public function test_admin_can_update_staff_role_and_school_assignments(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $schoolC = School::factory()->create();

        $staff = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old.staff@example.com',
            'username' => 'old.staff',
            'school_id' => $schoolA->id,
        ]);
        $staff->assignRole('examiner');
        $staff->schools()->sync([$schoolA->id => ['is_primary' => true]]);

        $response = $this->actingAs($this->admin)->put("/admin/users/staff/{$staff->id}", [
            'name' => 'Updated Staff',
            'email' => 'updated.staff@example.com',
            'username' => 'updated.staff',
            'school_ids' => [$schoolB->id, $schoolC->id],
            'primary_school_id' => $schoolC->id,
            'role' => 'invigilator',
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $staff->refresh();
        $this->assertSame('Updated Staff', $staff->name);
        $this->assertTrue($staff->hasRole('invigilator'));
        $this->assertFalse($staff->hasRole('examiner'));

        $this->assertDatabaseMissing('school_user', [
            'user_id' => $staff->id,
            'school_id' => $schoolA->id,
        ]);
        $this->assertDatabaseHas('school_user', [
            'user_id' => $staff->id,
            'school_id' => $schoolB->id,
            'is_primary' => false,
        ]);
        $this->assertDatabaseHas('school_user', [
            'user_id' => $staff->id,
            'school_id' => $schoolC->id,
            'is_primary' => true,
        ]);
    }

    public function test_staff_import_requires_file(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users/staff/import', []);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_admin_can_create_student_with_active_status_and_role(): void
    {
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/users/students', [
            'name' => 'Student One',
            'email' => 'student.one@example.com',
            'username' => 'student.one',
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'role' => 'candidate',
        ]);

        $response->assertRedirect(route('admin.students.index'));

        $student = User::where('email', 'student.one@example.com')->firstOrFail();
        $this->assertSame('active', $student->status);
        $this->assertTrue($student->hasRole('candidate'));
    }

    public function test_admin_can_update_student_and_sync_role(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $classA = SchoolClass::factory()->create();
        $classB = SchoolClass::factory()->create();

        $student = User::factory()->create([
            'name' => 'Original Student',
            'email' => 'original.student@example.com',
            'username' => 'original.student',
            'school_id' => $schoolA->id,
            'school_class_id' => $classA->id,
            'status' => 'active',
        ]);
        $student->assignRole('candidate');

        $response = $this->actingAs($this->admin)->put("/admin/users/students/{$student->id}", [
            'name' => 'Updated Student',
            'email' => 'updated.student@example.com',
            'username' => 'updated.student',
            'school_id' => $schoolB->id,
            'school_class_id' => $classB->id,
            'role' => 'prefect',
        ]);

        $response->assertRedirect(route('admin.students.index'));

        $student->refresh();
        $this->assertSame('Updated Student', $student->name);
        $this->assertSame($schoolB->id, $student->school_id);
        $this->assertSame($classB->id, $student->school_class_id);
        $this->assertTrue($student->hasRole('prefect'));
        $this->assertFalse($student->hasRole('candidate'));
    }

    public function test_student_import_requires_file(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users/students/import', []);

        $response->assertSessionHasErrors(['file']);
    }
}
