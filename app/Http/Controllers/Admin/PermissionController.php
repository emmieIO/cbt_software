<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the system permissions (Read Only).
     */
    public function index(): Response
    {
        return Inertia::render('Admin/RBAC/Permissions', [
            'permissions' => Permission::all(),
        ]);
    }
}
