<?php

namespace App\Mail;

use App\Helpers\RelatedCrmLink;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $task;
    public $user;
    public $relatedItem;
    public $related;

    public function __construct(Task $task, $user)
    {
        $this->task = $task;
        $this->user = $user;
        $this->related = RelatedCrmLink::fromEntity($task);
        $this->relatedItem = $this->getRelatedItem();
    }

    public function build()
    {
        return $this->subject('Task Reminder: ' . $this->task->name)
            ->view('emails.task-reminder')
            ->with([
                'task' => $this->task,
                'user' => $this->user,
                'relatedItem' => $this->relatedItem,
                'related' => $this->related,
            ]);
    }

    public function attachments(): array
    {
        return [];
    }

    private function getRelatedItem()
    {
        if ($this->task->related_type === 'lead') {
            return \App\Models\Lead::with('person')->find($this->task->related_id);
        }
        if ($this->task->related_type === 'deal') {
            return \App\Models\Deal::with('person')->find($this->task->related_id);
        }

        return null;
    }
}
