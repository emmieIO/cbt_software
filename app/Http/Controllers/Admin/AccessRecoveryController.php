<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Services\Admin\AccessRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessRecoveryController extends Controller
{
    public function __construct(protected AccessRecoveryService $accessRecoveryService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'school_id']);
        $data = $this->accessRecoveryService->getIndexData($filters);

        return Inertia::render('Admin/Users/AccessRecovery', [
            'users' => $data['users'],
            'roles' => $data['roles'],
            'branches' => School::query()->where('is_active', true)->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $this->accessRecoveryService->reassignRole($user, $request->string('role')->toString());

        return back()->with('success', "{$user->name} was reassigned successfully.");
    }
}

