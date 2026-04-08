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

        Permission::findOrCreate('admin:delete_class', 'web');

        // Preserve current behavior: any role that can manage setup can also delete classes,
        // unless explicitly tightened later in RBAC.
        $roles = Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', 'admin:manage_setup'))
            ->get();

        foreach ($roles as $role) {
            $role->givePermissionTo('admin:delete_class');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::query()->where('name', 'admin:delete_class')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
