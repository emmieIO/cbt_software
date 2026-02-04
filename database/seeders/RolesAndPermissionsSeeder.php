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

        // Create permissions
        $permissions = [
            'manage users',
            'manage settings',
            'create exams',
            'edit exams',
            'delete exams',
            'view exams',
            'take exams',
            'view results',
            'grade exams',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles and assign created permissions

        // Admin role
        $adminRole = Role::findOrCreate('admin');
        $adminRole->givePermissionTo(Permission::all());

        // Staff role
        $staffRole = Role::findOrCreate('staff');
        $staffRole->givePermissionTo([
            'create exams',
            'edit exams',
            'view exams',
            'view results',
            'grade exams',
        ]);

        // Student role
        $studentRole = Role::findOrCreate('student');
        $studentRole->givePermissionTo([
            'view exams',
            'take exams',
            'view results',
        ]);
    }
}
