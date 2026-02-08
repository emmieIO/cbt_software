<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

class AiQuestionsSeeded extends BaseNotification
{
    use Queueable;

    public function __construct(
        protected string $subjectName,
        protected string $topicName,
        protected int $count
    ) {
        parent::__construct();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        if ($this->count === 0) {
            return [
                'title' => 'AI Seeding Failed',
                'message' => "Unfortunately, we couldn't generate questions for {$this->subjectName} - {$this->topicName} at this time. Please try again with a smaller batch.",
                'type' => 'error',
                'action_url' => route('staff.questions.generate'),
            ];
        }

        return [
            'title' => 'AI Seeding Completed',
            'message' => "Successfully seeded {$this->count} questions for {$this->subjectName} - {$this->topicName}.",
            'type' => 'success',
            'action_url' => route('staff.questions.index'),
        ];
    }
}
