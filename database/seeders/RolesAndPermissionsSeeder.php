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
        // Isolation is maintained through Namespaced Permission Names (domain:action).
        $guard = 'web';

        $permissionsByDomain = [
            'sys' => [
                'sys:manage_schools' => 'Global control over campus/branch establishment.',
                'sys:manage_settings' => 'System-wide configuration and RBAC governance.',
            ],
            'admin' => [
                'admin:manage_users' => 'CRUD operations for Staff and Student accounts.',
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
        ];

        // Wipe old permissions to ensure no duplicates remain
        \Spatie\Permission\Models\Role::query()->delete();
        \Spatie\Permission\Models\Permission::query()->delete();
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->delete();
        \Illuminate\Support\Facades\DB::table('model_has_permissions')->delete();
        \Illuminate\Support\Facades\DB::table('model_has_roles')->delete();

        // Create all permissions
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
        $examiner->syncPermissions(Permission::where('name', 'not like', 'sys:%')
            ->where('name', 'not like', 'admin:manage_setup%')
            ->get());

        // 3. Candidate (Exam Taker - Students & Entrance Applicants)
        $candidate = Role::findOrCreate('candidate', $guard);
        $candidate->category = 'student';
        $candidate->save();
        $candidate->syncPermissions(['exam:view', 'exam:take', 'results:view']);
    }
}
