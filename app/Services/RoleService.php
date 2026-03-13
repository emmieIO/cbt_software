<?php

namespace App\Services;

use App\DTOs\RoleDTO;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function createRole(RoleDTO $dto): Role
    {
        $role = Role::create([
            'name' => $dto->name,
            'category' => $dto->category,
            'guard_name' => 'web',
        ]);

        $permissions = Permission::whereIn('name', $dto->permissions)
            ->where('guard_name', 'web')
            ->get();

        $role->syncPermissions($permissions);

        return $role;
    }

    public function updateRole(Role $role, RoleDTO $dto): bool
    {
        if ($role->name === 'super_admin') {
            return false;
        }

        $role->update([
            'name' => $dto->name,
            'category' => $dto->category,
            'guard_name' => 'web',
        ]);

        $permissions = Permission::whereIn('name', $dto->permissions)
            ->where('guard_name', 'web')
            ->get();

        $role->syncPermissions($permissions);

        return true;
    }

    public function deleteRole(Role $role): bool
    {
        if ($role->name === 'super_admin') {
            return false;
        }

        return $role->delete();
    }
}
