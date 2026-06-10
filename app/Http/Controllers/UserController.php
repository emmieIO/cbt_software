<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\SaveUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $userManagementService) {}

    public function index(): Response
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        $users = User::query()
            ->select(['id', 'name', 'username', 'email', 'role', 'permissions', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'availablePermissions' => [
                ['value' => User::PERMISSION_CREATE_QUESTIONS, 'label' => 'Create questions'],
                ['value' => User::PERMISSION_EDIT_QUESTIONS, 'label' => 'Edit questions'],
            ],
        ]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $this->userManagementService->create($request->payload());

        return back()->with('success', 'User created successfully.');
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        $this->userManagementService->update($user, $request->payload());

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        if ($user->id === request()->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
