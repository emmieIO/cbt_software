<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\RoleDTO;
use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(protected RoleService $roleService) {}

    public function index(): Response
    {
        return Inertia::render('Admin/RBAC/Roles', [
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $dto = RoleDTO::fromRequest($request);
        $this->roleService->createRole($dto);

        return back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $dto = RoleDTO::fromRequest($request);
        $this->roleService->updateRole($role, $dto);

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $deleted = $this->roleService->deleteRole($role);

        if (! $deleted) {
            return back()->with('error', 'Cannot delete the admin role.');
        }

        return back()->with('success', 'Role deleted successfully.');
    }
}
