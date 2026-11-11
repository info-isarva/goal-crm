<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskReminder extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'remind_at',
        'reminder_type',
        'email_sent',
        'notification_sent',
        'email_sent_at',
        'notification_sent_at',
    ];

    protected $dates = ['remind_at', 'email_sent_at', 'notification_sent_at', 'created_at', 'updated_at'];

    protected $casts = [
        'email_sent' => 'boolean',
        'notification_sent' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
