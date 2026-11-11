<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $task;
    public $reminder;

    /**
     * Create a new notification instance.
     * Accept either a Task or a TaskReminder (preferred) so senders
     * can provide remind_at context.
     */
    public function __construct($payload)
    {
        if ($payload instanceof TaskReminder) {
            $this->reminder = $payload;
            $this->task = $payload->task;
        } else {
            $this->task = $payload;
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $related = \App\Helpers\RelatedCrmLink::fromEntity($this->task);
        $relatedLink = $related['url'] ?? '';
        $remindAt = null;
        if ($this->reminder && $this->reminder->remind_at) {
            $remindAt = $this->reminder->remind_at;
        }

        $message = 'Reminder: Task "' . ($this->task->name ?? 'task') . '" is due';
        if ($this->task && $this->task->due_at) {
            $message .= ' at ' . (\Carbon\Carbon::parse($this->task->due_at)->format('d M, Y H:i'));
        }
        if ($related) {
            $message .= ' for ' . $related['label'] . ' "' . $related['name'] . '"';
        }
        if ($remindAt) {
            $message .= ' (reminder at ' . (\Carbon\Carbon::parse($remindAt)->format('d M, Y H:i')) . ')';
        }

        // Build a link directly to the related page and fragment to the precise task
        $taskFragment = $this->task && $this->task->id ? '#task-' . $this->task->id : '';
        $directLink = $relatedLink ? ($relatedLink . $taskFragment) : ($this->task && $this->task->id ? route('tasks.show', $this->task->id) : '');

        return [
            'task_id' => $this->task->id ?? null,
            'task_name' => $this->task->name ?? null,
            'description' => $this->task->description ?? null,
            'due_at' => $this->task->due_at ?? null,
            'related_type' => $related['type'] ?? ($this->task->related_type ?? null),
            'related_id' => $this->task->related_id ?? null,
            'related_name' => $related['name'] ?? null,
            'related_link' => $relatedLink,
            // Direct link to the related page with a fragment pointing to the task element
            'task_link' => $directLink,
            'remind_at' => $remindAt ? (string) $remindAt : null,
            'message' => $message,
        ];
    }
}
