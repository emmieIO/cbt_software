<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

abstract class BaseNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->id = (string) Str::ulid();
    }
}
