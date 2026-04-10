<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\RoleDTO;
use App\Http\Controllers\Controller;
use App\Models\User;
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
        $usersWithoutRolesCount = User::query()
            ->doesntHave('roles')
            ->count();

        return Inertia::render('Admin/RBAC/Roles', [
            'roles' => Role::with('permissions')->withCount('users')->get(),
            'permissions' => Permission::all(),
            'usersWithoutRolesCount' => $usersWithoutRolesCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'category' => ['required', 'string', 'in:admin,staff,student'],
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
            'category' => ['required', 'string', 'in:admin,staff,student'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $dto = RoleDTO::fromRequest($request);
        $this->roleService->updateRole($role, $dto);

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $result = $this->roleService->deleteRole($role);

        if (! $result['deleted']) {
            return back()->with('error', $result['reason'] ?? 'Role could not be deleted.');
        }

        return back()->with('success', 'Role deleted successfully.');
    }
}
