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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::findOrCreate('access:cross-level-authoring', 'web');

        // Smooth rollout: any role already granted question cross-level authoring
        // receives the new shared authoring permission as well.
        $roles = Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', 'bank:create_cross_level'))
            ->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::query()->where('name', 'access:cross-level-authoring')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
