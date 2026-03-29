<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // We now use a Single Guard Strategy (web) for the entire application database.
        $guard = 'web';

        $permissionsByDomain = [
            'sys' => [
                'sys:manage_schools' => 'Global control over campus/branch establishment.',
                'sys:manage_settings' => 'System-wide configuration and RBAC governance.',
            ],
            'admin' => [
                'admin:manage_setup' => 'Academic structure configuration (Sessions, Terms, Classes).',
                'admin:manage_curriculum' => 'Management of Subjects and Topics.',
                'admin:manage_enrollment' => 'Student class mapping and promotion management.',
                'admin:manage_admissions' => 'Entrance exam candidate and admission lifecycle management.',
                'admin:manage_batches' => 'Entrance exam grouping and batch configuration.',
            ],
            'bank' => [
                'bank:view' => 'Access to browse the question repository.',
                'bank:create' => 'Contribution of new questions to the pool.',
                'bank:edit' => 'Modification of existing repository items.',
                'bank:delete' => 'Permanent removal of items from the bank.',
                'bank:manage' => 'Bulk operations and advanced bank management.',
                'bank:use_ai' => 'Access to the AI Question Generation Lab.',
                'bank:export' => 'Data exfiltration and Excel/CSV repository exports.',
            ],
            'exam' => [
                'exam:create' => 'Initialization of new assessment configurations.',
                'exam:edit' => 'Modification of live/scheduled exam settings.',
                'exam:delete' => 'Removal of assessments from the vault.',
                'exam:view' => 'General visibility of scheduled assessments.',
                'exam:take' => 'Entry into the secure CBT environment.',
                'exam:manage_entrance' => 'Specialized control over admission testing blueprints.',
            ],
            'results' => [
                'results:view' => 'Access to score sheets and performance analytics.',
                'results:grade' => 'Manual scoring adjustment and correction authority.',
            ],
            'staff' => [
                'staff:view' => 'View staff member profiles and directories.',
                'staff:create' => 'Register new staff members into the system.',
                'staff:edit' => 'Modify staff member profiles and roles.',
                'staff:delete' => 'Remove or deactivate staff accounts.',
            ],
            'student' => [
                'student:view' => 'View candidates within assigned campus.',
                'student:create' => 'Register new student accounts.',
                'student:edit' => 'Modify student profiles and details.',
                'student:delete' => 'Remove or deactivate student accounts.',
            ],
        ];

        // Create all permissions (update existing ones)
        foreach ($permissionsByDomain as $domain => $permissions) {
            foreach ($permissions as $name => $description) {
                Permission::findOrCreate($name, $guard);
            }
        }

        // Define Roles and sync their specific Namespaced Permissions

        // 1. Super Admin (Total System Ownership)
        $superAdmin = Role::findOrCreate('super_admin', $guard);
        $superAdmin->category = 'admin';
        $superAdmin->save();
        $superAdmin->syncPermissions(Permission::all());

        // 2. Examiner (Academic Authority - Question & Exam Management)
        $examiner = Role::findOrCreate('examiner', $guard);
        $examiner->category = 'staff';
        $examiner->save();
        $examinerPermissions = Permission::where('name', 'like', 'bank:%')
            ->orWhere('name', 'like', 'exam:%')
            ->orWhere('name', 'like', 'results:%')
            ->orWhere('name', 'student:view')
            ->get()
            ->reject(fn ($p) => $p->name === 'exam:manage_entrance');

        $examiner->syncPermissions($examinerPermissions);

        // 3. Candidate (Exam Taker - Students & Entrance Applicants)
        $candidate = Role::findOrCreate('candidate', $guard);
        $candidate->category = 'student';
        $candidate->save();
        $candidate->syncPermissions(['exam:view', 'exam:take', 'results:view']);
    }
}
