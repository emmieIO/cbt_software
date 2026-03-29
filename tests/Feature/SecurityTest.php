<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed basic roles and permissions
        $adminRole = Role::findOrCreate('super_admin', 'web');
        $adminRole->category = 'admin';
        $adminRole->save();

        $studentRole = Role::findOrCreate('candidate', 'web');
        $studentRole->category = 'student';
        $studentRole->save();

        Role::findOrCreate('examiner', 'web');

        \Spatie\Permission\Models\Permission::findOrCreate('access:admin-portal', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('access:student-portal', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('sys:manage_settings', 'web');

        $adminRole->givePermissionTo('access:admin-portal');
        $adminRole->givePermissionTo('sys:manage_settings');
    }

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = User::factory()->create();
        $student->assignRole('candidate');

        $response = $this->actingAs($student)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_any_portal_via_bypass(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        // Give explicit admin access permission
        $admin->givePermissionTo('access:admin-portal');

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);

        // Test student portal access (should bypass because of super_admin role)
        $response = $this->actingAs($admin)->get('/student/dashboard');
        $response->assertStatus(200);
    }

    public function test_login_failure_is_generic_to_prevent_enumeration(): void
    {
        // Bind a mock session to the request so AuthService can invalidate it without crashing
        $this->withSession([]);
        request()->setLaravelSession(session()->driver());

        $authService = app(\App\Services\AuthService::class);

        // 1. Existing user, wrong password
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $errorMsg1 = '';
        try {
            $authService->login(['email' => $user->email, 'password' => 'wrong-password'], false, 'access:admin-portal');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMsg1 = $e->errors()['login_id'][0];
        }

        // 2. Existing user, correct password, WRONG portal (missing permission)
        $errorMsg2 = '';
        try {
            $authService->login(['email' => $user->email, 'password' => 'correct-password'], false, 'access:admin-portal');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMsg2 = $e->errors()['login_id'][0];
        }

        // Both messages MUST be identical
        $this->assertEquals($errorMsg1, $errorMsg2);
        $this->assertEquals(trans('auth.failed'), $errorMsg1);
    }
}
