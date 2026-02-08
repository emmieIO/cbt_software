<?php

namespace App\Services;

use App\DTOs\RoleDTO;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function createRole(RoleDTO $dto): Role
    {
        $role = Role::create(['name' => $dto->name]);
        $role->syncPermissions($dto->permissions);

        return $role;
    }

    public function updateRole(Role $role, RoleDTO $dto): bool
    {
        $role->update(['name' => $dto->name]);
        $role->syncPermissions($dto->permissions);

        return true;
    }

    public function deleteRole(Role $role): bool
    {
        if ($role->name === 'admin') {
            return false;
        }

        return $role->delete();
    }
}
