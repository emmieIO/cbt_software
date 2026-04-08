<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('bank:create_cross_level', 'web');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::query()->where('name', 'bank:create_cross_level')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
