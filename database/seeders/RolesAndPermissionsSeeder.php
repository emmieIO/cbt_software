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

        // System Management
        Permission::findOrCreate('manage users');
        Permission::findOrCreate('manage settings');
        Permission::findOrCreate('manage school setup');
        Permission::findOrCreate('manage curriculum');
        Permission::findOrCreate('manage enrollment');

        // Question Bank Management
        Permission::findOrCreate('view questions');
        Permission::findOrCreate('create questions');
        Permission::findOrCreate('edit questions');
        Permission::findOrCreate('delete questions');
        Permission::findOrCreate('manage question bank'); // Bulk actions, version management
        Permission::findOrCreate('use ai lab');
        Permission::findOrCreate('export questions');

        // Exam Management
        Permission::findOrCreate('create exams');
        Permission::findOrCreate('edit exams');
        Permission::findOrCreate('delete exams');
        Permission::findOrCreate('view exams');
        Permission::findOrCreate('take exams');

        // Results
        Permission::findOrCreate('view results');
        Permission::findOrCreate('grade exams');

        // Admin role
        $adminRole = Role::findOrCreate('admin');
        $adminRole->givePermissionTo(Permission::all());

        // Subject Lead role (Senior Teachers / HODs)
        $subjectLeadRole = Role::findOrCreate('subject_lead');
        $subjectLeadRole->givePermissionTo([
            'view questions',
            'create questions',
            'edit questions',
            'manage question bank',
            'use ai lab',
            'export questions',
            'view exams',
            'view results',
            'grade exams',
        ]);

        // Staff role (Regular Teachers)
        $staffRole = Role::findOrCreate('staff');
        $staffRole->givePermissionTo([
            'view questions',
            'create questions',
            'edit questions',
            'delete questions',
            'use ai lab',
            'export questions',
            'view exams',
            'create exams',
            'edit exams',
            'delete exams',
            'view results',
        ]);

        // Student role
        $studentRole = Role::findOrCreate('student');
        $studentRole->givePermissionTo([
            'view exams',
            'take exams',
            'view results',
        ]);

        // Candidate role (Prospective Students)
        $candidateRole = Role::findOrCreate('candidate');
        $candidateRole->givePermissionTo([
            'take exams',
        ]);
    }
}
