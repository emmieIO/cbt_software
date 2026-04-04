<?php

namespace App\Services;

use App\Models\User;

class NotificationService
{
    /**
     * Mark a specific notification as read for the authenticated user.
     */
    public function markAsRead(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }
}
