<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AiQuestionsSeeded extends Notification
{
    use Queueable;

    public function __construct(
        protected string $subjectName,
        protected string $topicName,
        protected int $count
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'AI Seeding Completed',
            'message' => "Successfully seeded {$this->count} questions for {$this->subjectName} - {$this->topicName}.",
            'type' => 'success',
            'action_url' => route('staff.questions.index'),
        ];
    }
}
