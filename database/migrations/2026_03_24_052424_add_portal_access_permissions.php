<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'access:admin-portal',
            'access:staff-portal',
            'access:student-portal',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign to roles
        $admin = Role::where('name', 'super_admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        $examiner = Role::where('name', 'examiner')->first();
        if ($examiner) {
            $examiner->givePermissionTo('access:staff-portal');
        }

        $candidate = Role::where('name', 'candidate')->first();
        if ($candidate) {
            $candidate->givePermissionTo('access:student-portal');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'access:admin-portal',
            'access:staff-portal',
            'access:student-portal',
        ])->delete();
    }
};
