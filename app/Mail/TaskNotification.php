<?php

namespace App\Mail;

use App\Helpers\RelatedCrmLink;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $user;
    public $related;

    public function __construct(Task $task, $user)
    {
        $this->task = $task;
        $this->user = $user;
        $this->related = RelatedCrmLink::fromEntity($task);
    }

    public function build()
    {
        return $this->subject('New Task: ' . $this->task->name)
            ->view('emails.task_notification')
            ->with([
                'task' => $this->task,
                'user' => $this->user,
                'related' => $this->related,
            ]);
    }
}
