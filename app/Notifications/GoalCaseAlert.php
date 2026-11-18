<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GoalCaseAlert extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $eventKey = 'goal_case',
        public string $type = 'task',
        public ?string $ref = null,
        public bool $action = true
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'related_link' => $this->url,
            'event' => $this->eventKey,
            'type' => $this->type,
            'ref' => $this->ref,
            'action' => $this->action,
        ];
    }
}
