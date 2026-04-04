<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $this->notificationService->markAsRead($request->user(), $id);

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return back();
    }
}
